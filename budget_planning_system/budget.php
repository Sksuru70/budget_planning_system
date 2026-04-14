<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireLogin();
$uid = uid();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name  = trim($_POST['category_name'] ?? '');
        $limit = (float)($_POST['budget_limit'] ?? 0);
        $icon  = $_POST['icon'] ?? 'bi-tag';
        $color = $_POST['color'] ?? '#4361ee';
        $period= $_POST['period_type'] ?? 'monthly';
        if ($name && $limit > 0) {
            $pdo->prepare("INSERT INTO budget_categories (user_id,category_name,budget_limit,icon,color,period_type) VALUES (?,?,?,?,?,?)")->execute([$uid,$name,$limit,$icon,$color,$period]);
            setFlash('success','Budget category added!');
        } else { setFlash('danger','Name and limit are required.'); }
    } elseif ($action === 'edit') {
        $cid   = (int)$_POST['category_id'];
        $name  = trim($_POST['category_name'] ?? '');
        $limit = (float)($_POST['budget_limit'] ?? 0);
        $icon  = $_POST['icon'] ?? 'bi-tag';
        $color = $_POST['color'] ?? '#4361ee';
        $period= $_POST['period_type'] ?? 'monthly';
        if ($name && $limit > 0) {
            $pdo->prepare("UPDATE budget_categories SET category_name=?,budget_limit=?,icon=?,color=?,period_type=? WHERE category_id=? AND user_id=?")->execute([$name,$limit,$icon,$color,$period,$cid,$uid]);
            setFlash('success','Category updated!');
        }
    } elseif ($action === 'delete') {
        $cid = (int)$_POST['category_id'];
        $pdo->prepare("DELETE FROM budget_categories WHERE category_id=? AND user_id=?")->execute([$cid,$uid]);
        setFlash('success','Category deleted.');
    }
    header('Location: budget.php'); exit;
}

$month = date('Y-m');
$cats = $pdo->prepare("SELECT bc.*, COALESCE(SUM(t.amount),0) AS spent FROM budget_categories bc LEFT JOIN transactions t ON t.category_id=bc.category_id AND t.type='expense' AND DATE_FORMAT(t.transaction_date,'%Y-%m')=? WHERE bc.user_id=? GROUP BY bc.category_id ORDER BY bc.category_name");
$cats->execute([$month, $uid]);
$catRows = $cats->fetchAll();

$icons = ['bi-tag','bi-cup-hot','bi-car-front','bi-house','bi-heart-pulse','bi-film','bi-book','bi-bag','bi-phone','bi-lightning','bi-airplane','bi-gift','bi-music-note','bi-cart','bi-cash-stack'];
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Budget Categories</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:14px">
      <button id="sidebarToggle" style="display:none;background:none;border:none;font-size:1.4rem;cursor:pointer"><i class="bi bi-list"></i></button>
      <span class="page-title">Budget Categories</span>
    </div>
    <button onclick="document.getElementById('addModal').style.display='flex'" class="btn-primary-custom"><i class="bi bi-plus-lg"></i> Add Category</button>
  </div>
  <div class="content-area">
    <?php if ($flash): ?><div class="flash-alert flash-<?= $flash['type'] ?>"><i class="bi bi-info-circle"></i><?= htmlspecialchars($flash['msg']) ?></div><?php endif; ?>
    <p style="color:var(--text-muted);font-size:.88rem;margin-bottom:20px">Managing budget for <strong><?= date('F Y') ?></strong></p>

    <?php if ($catRows): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px">
    <?php foreach ($catRows as $c):
      $pct   = $c['budget_limit'] > 0 ? min(100,($c['spent']/$c['budget_limit'])*100) : 0;
      $color = $pct>=100?'var(--danger)':($pct>=80?'var(--warning)':'var(--primary)');
      $rem   = max(0, $c['budget_limit'] - $c['spent']);
    ?>
    <div class="bcard">
      <div class="bcard-body">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
          <div style="display:flex;align-items:center;gap:12px">
            <div class="icon-box" style="background:<?= $c['color'] ?>22;color:<?= $c['color'] ?>;font-size:1.25rem;width:44px;height:44px;border-radius:12px"><i class="bi <?= $c['icon'] ?>"></i></div>
            <div>
              <div style="font-weight:800;color:var(--dark)"><?= htmlspecialchars($c['category_name']) ?></div>
              <div style="font-size:.72rem;color:var(--text-muted);font-weight:600;text-transform:capitalize"><?= $c['period_type'] ?></div>
            </div>
          </div>
          <div style="display:flex;gap:6px">
            <button onclick='openEdit(<?= json_encode($c) ?>)' style="background:#f1f5f9;border:none;padding:6px 10px;border-radius:8px;cursor:pointer;color:var(--primary)"><i class="bi bi-pencil"></i></button>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="category_id" value="<?= $c['category_id'] ?>">
              <button type="submit" data-confirm="Delete '<?= htmlspecialchars($c['category_name']) ?>'?" style="background:#fee2e2;border:none;padding:6px 10px;border-radius:8px;cursor:pointer;color:var(--danger)"><i class="bi bi-trash"></i></button>
            </form>
          </div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.82rem;font-weight:600;margin-bottom:8px">
          <span style="color:var(--text-muted)">Spent: <span style="color:#f72585"><?= fmt($c['spent']) ?></span></span>
          <span style="color:var(--text-muted)">Limit: <span style="color:var(--dark)"><?= fmt($c['budget_limit']) ?></span></span>
        </div>
        <div class="progress" style="margin-bottom:8px"><div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $color ?>"></div></div>
        <div style="display:flex;justify-content:space-between;font-size:.78rem;font-weight:700">
          <span style="color:<?= $color ?>"><?= round($pct) ?>% used</span>
          <span style="color:var(--success)">₹<?= number_format($rem,2) ?> remaining</span>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="bcard"><div class="bcard-body"><div class="empty-state"><i class="bi bi-pie-chart"></i><p>No budget categories yet. Create one to start tracking!</p><br><button onclick="document.getElementById('addModal').style.display='flex'" class="btn-primary-custom"><i class="bi bi-plus-lg"></i> Add Category</button></div></div></div>
    <?php endif; ?>
  </div>
</div>

<!-- Add Modal -->
<div id="addModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:20px;padding:32px;width:100%;max-width:440px;margin:20px;max-height:90vh;overflow-y:auto">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px">
      <h5 style="font-weight:800;margin:0">Add Budget Category</h5>
      <button onclick="document.getElementById('addModal').style.display='none'" style="background:none;border:none;font-size:1.4rem;cursor:pointer">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div style="margin-bottom:14px"><label class="form-label">Category Name</label><input type="text" name="category_name" class="form-control" placeholder="e.g. Food & Dining" required></div>
      <div style="margin-bottom:14px"><label class="form-label">Monthly Budget Limit (₹)</label><input type="number" name="budget_limit" class="form-control" placeholder="5000.00" step="0.01" min="1" required></div>
      <div style="margin-bottom:14px"><label class="form-label">Period</label><select name="period_type" class="form-select"><option value="monthly">Monthly</option><option value="yearly">Yearly</option></select></div>
      <div style="margin-bottom:14px">
        <label class="form-label">Icon</label>
        <select name="icon" class="form-select">
          <?php foreach ($icons as $ic): ?><option value="<?= $ic ?>"><?= $ic ?></option><?php endforeach; ?>
        </select>
      </div>
      <div style="margin-bottom:22px"><label class="form-label">Color</label><input type="color" name="color" value="#4361ee" class="form-control" style="height:44px;padding:4px"></div>
      <button type="submit" class="btn-primary-custom" style="width:100%;justify-content:center;padding:12px"><i class="bi bi-plus-lg"></i> Add Category</button>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:20px;padding:32px;width:100%;max-width:440px;margin:20px;max-height:90vh;overflow-y:auto">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px">
      <h5 style="font-weight:800;margin:0">Edit Category</h5>
      <button onclick="document.getElementById('editModal').style.display='none'" style="background:none;border:none;font-size:1.4rem;cursor:pointer">&times;</button>
    </div>
    <form method="POST" id="editForm">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="category_id" id="edit_id">
      <div style="margin-bottom:14px"><label class="form-label">Category Name</label><input type="text" name="category_name" id="edit_name" class="form-control" required></div>
      <div style="margin-bottom:14px"><label class="form-label">Budget Limit (₹)</label><input type="number" name="budget_limit" id="edit_limit" class="form-control" step="0.01" min="1" required></div>
      <div style="margin-bottom:14px"><label class="form-label">Period</label><select name="period_type" id="edit_period" class="form-select"><option value="monthly">Monthly</option><option value="yearly">Yearly</option></select></div>
      <div style="margin-bottom:14px"><label class="form-label">Icon</label><select name="icon" id="edit_icon" class="form-select"><?php foreach ($icons as $ic): ?><option value="<?= $ic ?>"><?= $ic ?></option><?php endforeach; ?></select></div>
      <div style="margin-bottom:22px"><label class="form-label">Color</label><input type="color" name="color" id="edit_color" class="form-control" style="height:44px;padding:4px"></div>
      <button type="submit" class="btn-primary-custom" style="width:100%;justify-content:center;padding:12px"><i class="bi bi-check-lg"></i> Update Category</button>
    </form>
  </div>
</div>
<script src="assets/js/main.js"></script>
<script>
function openEdit(c) {
  document.getElementById('edit_id').value    = c.category_id;
  document.getElementById('edit_name').value  = c.category_name;
  document.getElementById('edit_limit').value = c.budget_limit;
  document.getElementById('edit_period').value= c.period_type;
  document.getElementById('edit_icon').value  = c.icon;
  document.getElementById('edit_color').value = c.color;
  document.getElementById('editModal').style.display = 'flex';
}
</script>
</body>
</html>
