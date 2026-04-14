<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireLogin();
$uid = uid();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name   = trim($_POST['goal_name'] ?? '');
        $target = (float)($_POST['target_amount'] ?? 0);
        $saved  = (float)($_POST['saved_amount'] ?? 0);
        $date   = $_POST['target_date'] ?: null;
        $desc   = trim($_POST['description'] ?? '');
        if ($name && $target > 0) {
            $pdo->prepare("INSERT INTO savings_goals (user_id,goal_name,target_amount,saved_amount,target_date,description) VALUES (?,?,?,?,?,?)")->execute([$uid,$name,$target,$saved,$date,$desc]);
            setFlash('success','Savings goal created!');
        } else { setFlash('danger','Goal name and target amount are required.'); }
    } elseif ($action === 'add_savings') {
        $gid    = (int)$_POST['goal_id'];
        $amount = (float)($_POST['add_amount'] ?? 0);
        if ($amount > 0) {
            $pdo->prepare("UPDATE savings_goals SET saved_amount = LEAST(saved_amount + ?, target_amount) WHERE goal_id=? AND user_id=?")->execute([$amount,$gid,$uid]);
            // Check if achieved
            $g = $pdo->prepare("SELECT * FROM savings_goals WHERE goal_id=? AND user_id=?");
            $g->execute([$gid,$uid]);
            $goal = $g->fetch();
            if ($goal && $goal['saved_amount'] >= $goal['target_amount']) {
                $pdo->prepare("UPDATE savings_goals SET status='achieved' WHERE goal_id=?")->execute([$gid]);
                setFlash('success','🎉 Congratulations! You achieved your savings goal!');
            } else {
                setFlash('success','₹'.number_format($amount,2).' added to savings!');
            }
        }
    } elseif ($action === 'delete') {
        $gid = (int)$_POST['goal_id'];
        $pdo->prepare("DELETE FROM savings_goals WHERE goal_id=? AND user_id=?")->execute([$gid,$uid]);
        setFlash('success','Goal deleted.');
    }
    header('Location: savings.php'); exit;
}

$goals = $pdo->prepare("SELECT * FROM savings_goals WHERE user_id=? ORDER BY status='in_progress' DESC, created_at DESC");
$goals->execute([$uid]);
$goalRows = $goals->fetchAll();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Savings Goals</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:14px">
      <button id="sidebarToggle" style="display:none;background:none;border:none;font-size:1.4rem;cursor:pointer"><i class="bi bi-list"></i></button>
      <span class="page-title">Savings Goals</span>
    </div>
    <button onclick="document.getElementById('addModal').style.display='flex'" class="btn-primary-custom"><i class="bi bi-plus-lg"></i> New Goal</button>
  </div>
  <div class="content-area">
    <?php if ($flash): ?><div class="flash-alert flash-<?= $flash['type'] ?>"><i class="bi bi-info-circle"></i><?= htmlspecialchars($flash['msg']) ?></div><?php endif; ?>

    <?php if ($goalRows):
      $totalTarget = array_sum(array_column($goalRows,'target_amount'));
      $totalSaved  = array_sum(array_column($goalRows,'saved_amount'));
      $achieved    = count(array_filter($goalRows, fn($g) => $g['status']==='achieved'));
    ?>
    <!-- Summary row -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px">
      <div class="stat-card"><div class="stat-icon" style="background:#f3e8ff"><i class="bi bi-piggy-bank" style="color:#7209b7"></i></div><div><div class="label">Total Goals</div><div class="value"><?= count($goalRows) ?></div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:#dcfce7"><i class="bi bi-trophy" style="color:#16a34a"></i></div><div><div class="label">Achieved</div><div class="value" style="color:#06d6a0"><?= $achieved ?></div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:#dbeafe"><i class="bi bi-cash-stack" style="color:#2563eb"></i></div><div><div class="label">Total Saved</div><div class="value" style="color:var(--primary)"><?= fmt($totalSaved) ?></div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:#fef3c7"><i class="bi bi-bullseye" style="color:#d97706"></i></div><div><div class="label">Total Target</div><div class="value"><?= fmt($totalTarget) ?></div></div></div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px">
    <?php foreach ($goalRows as $g):
      $pct  = $g['target_amount'] > 0 ? min(100, ($g['saved_amount']/$g['target_amount'])*100) : 0;
      $rem  = max(0, $g['target_amount'] - $g['saved_amount']);
      $daysLeft = $g['target_date'] ? (int)ceil((strtotime($g['target_date'])-time())/(86400)) : null;
      $colors = ['#4361ee','#f72585','#7209b7','#06d6a0','#4cc9f0','#ffd166'];
      $col = $colors[crc32($g['goal_name'])%count($colors)];
    ?>
    <div class="goal-card">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px">
        <div style="display:flex;align-items:center;gap:12px">
          <div style="width:46px;height:46px;border-radius:14px;background:<?= $col ?>22;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:<?= $col ?>">
            <?= $g['status']==='achieved' ? '🏆' : '🎯' ?>
          </div>
          <div>
            <div class="goal-name"><?= htmlspecialchars($g['goal_name']) ?></div>
            <?php if ($g['target_date']): ?>
            <div style="font-size:.72rem;color:var(--text-muted);font-weight:600">
              <?= $daysLeft > 0 ? $daysLeft.' days left' : ($daysLeft == 0 ? 'Due today' : 'Overdue') ?> — <?= date('d M Y', strtotime($g['target_date'])) ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php if ($g['status']==='achieved'): ?>
        <span class="badge-success">Achieved!</span>
        <?php else: ?><span class="badge-pending">In Progress</span><?php endif; ?>
      </div>
      <?php if ($g['description']): ?><p style="font-size:.82rem;color:var(--text-muted);margin-bottom:12px"><?= htmlspecialchars($g['description']) ?></p><?php endif; ?>
      <div class="goal-amounts"><span>Saved: <strong><?= fmt($g['saved_amount']) ?></strong></span><span>Target: <strong><?= fmt($g['target_amount']) ?></strong></span></div>
      <div class="progress" style="margin-bottom:8px"><div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $g['status']==='achieved'?'var(--success)':'#4361ee' ?>"></div></div>
      <div style="display:flex;justify-content:space-between;font-size:.78rem;font-weight:700;margin-bottom:14px">
        <span style="color:var(--primary)"><?= round($pct,1) ?>% complete</span>
        <span style="color:var(--text-muted)"><?= fmt($rem) ?> remaining</span>
      </div>
      <?php if ($g['status'] !== 'achieved'): ?>
      <form method="POST" style="display:flex;gap:8px">
        <input type="hidden" name="action" value="add_savings">
        <input type="hidden" name="goal_id" value="<?= $g['goal_id'] ?>">
        <input type="number" name="add_amount" class="form-control" placeholder="Add ₹ amount" step="0.01" min="1" style="flex:1;padding:8px 12px;font-size:.85rem" required>
        <button type="submit" class="btn-success-custom" style="white-space:nowrap"><i class="bi bi-plus-lg"></i> Add</button>
      </form>
      <?php endif; ?>
      <div style="margin-top:10px;text-align:right">
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="goal_id" value="<?= $g['goal_id'] ?>">
          <button type="submit" class="btn-danger-custom" data-confirm="Delete this goal?" style="padding:4px 12px;font-size:.75rem"><i class="bi bi-trash"></i> Delete</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="bcard"><div class="bcard-body"><div class="empty-state"><i class="bi bi-piggy-bank"></i><p>No savings goals yet. Start saving for something important!</p><br><button onclick="document.getElementById('addModal').style.display='flex'" class="btn-primary-custom"><i class="bi bi-plus-lg"></i> Create Goal</button></div></div></div>
    <?php endif; ?>
  </div>
</div>

<!-- Add Modal -->
<div id="addModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:20px;padding:32px;width:100%;max-width:440px;margin:20px;max-height:90vh;overflow-y:auto">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px">
      <h5 style="font-weight:800;margin:0">Create Savings Goal</h5>
      <button onclick="document.getElementById('addModal').style.display='none'" style="background:none;border:none;font-size:1.4rem;cursor:pointer">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div style="margin-bottom:14px"><label class="form-label">Goal Name</label><input type="text" name="goal_name" class="form-control" placeholder="e.g. Emergency Fund" required></div>
      <div style="margin-bottom:14px"><label class="form-label">Target Amount (₹)</label><input type="number" name="target_amount" class="form-control" placeholder="50000.00" step="0.01" min="1" required></div>
      <div style="margin-bottom:14px"><label class="form-label">Already Saved (₹)</label><input type="number" name="saved_amount" class="form-control" placeholder="0.00" step="0.01" min="0" value="0"></div>
      <div style="margin-bottom:14px"><label class="form-label">Target Date</label><input type="date" name="target_date" class="form-control" min="<?= date('Y-m-d') ?>"></div>
      <div style="margin-bottom:22px"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2" placeholder="Why are you saving for this?"></textarea></div>
      <button type="submit" class="btn-primary-custom" style="width:100%;justify-content:center;padding:12px"><i class="bi bi-piggy-bank"></i> Create Goal</button>
    </form>
  </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
