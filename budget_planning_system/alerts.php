<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireLogin();
$uid = uid();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'mark_all') {
        $pdo->prepare("UPDATE alerts SET is_read=1 WHERE user_id=?")->execute([$uid]);
        setFlash('success','All alerts marked as read.');
    } elseif (($_POST['action'] ?? '') === 'clear_all') {
        $pdo->prepare("DELETE FROM alerts WHERE user_id=? AND is_read=1")->execute([$uid]);
        setFlash('success','Read alerts cleared.');
    }
    header('Location: alerts.php'); exit;
}

// Mark individual as read
if (isset($_GET['read'])) {
    $pdo->prepare("UPDATE alerts SET is_read=1 WHERE alert_id=? AND user_id=?")->execute([(int)$_GET['read'],$uid]);
    header('Location: alerts.php'); exit;
}

checkAndGenerateAlerts($pdo, $uid);

$alerts = $pdo->prepare("SELECT a.*,bc.category_name,bc.icon FROM alerts a LEFT JOIN budget_categories bc ON bc.category_id=a.category_id WHERE a.user_id=? ORDER BY a.created_at DESC");
$alerts->execute([$uid]);
$alertRows = $alerts->fetchAll();
$unread = count(array_filter($alertRows, fn($a)=>!$a['is_read']));

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Alerts – Budget Planning System</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:14px">
      <button id="sidebarToggle" style="display:none;background:none;border:none;font-size:1.4rem;cursor:pointer"><i class="bi bi-list"></i></button>
      <span class="page-title">Alerts & Notifications</span>
    </div>
    <div style="display:flex;gap:10px">
      <?php if ($unread > 0): ?>
      <form method="POST"><input type="hidden" name="action" value="mark_all"><button type="submit" class="btn-outline-custom" style="padding:8px 16px;font-size:.82rem"><i class="bi bi-check-all"></i> Mark All Read</button></form>
      <?php endif; ?>
      <form method="POST"><input type="hidden" name="action" value="clear_all"><button type="submit" class="btn-outline-custom" style="padding:8px 16px;font-size:.82rem"><i class="bi bi-trash"></i> Clear Read</button></form>
    </div>
  </div>
  <div class="content-area">
    <?php if ($flash): ?><div class="flash-alert flash-<?= $flash['type'] ?>"><i class="bi bi-info-circle"></i><?= htmlspecialchars($flash['msg']) ?></div><?php endif; ?>

    <?php if ($unread > 0): ?>
    <div class="flash-alert flash-warning" style="margin-bottom:20px"><i class="bi bi-bell-fill"></i> You have <strong><?= $unread ?> unread</strong> alert<?= $unread>1?'s':'' ?>.</div>
    <?php endif; ?>

    <?php if ($alertRows): ?>
    <?php foreach ($alertRows as $a): $isEx = $a['alert_type']==='exceeded'; ?>
    <div class="alert-item <?= $isEx?'alert-exceeded-item':'alert-warning-item' ?>" style="<?= !$a['is_read']?'border-left-width:5px':'' ?>; margin-bottom:10px">
      <div style="margin-top:2px;font-size:1.3rem"><?= $isEx?'🚨':'⚠️' ?></div>
      <div style="flex:1">
        <div class="alert-msg"><?= htmlspecialchars($a['message']) ?></div>
        <div class="alert-time">
          <?php if ($a['category_name']): ?><i class="bi <?= $a['icon'] ?>"></i> <?= htmlspecialchars($a['category_name']) ?> &nbsp;•&nbsp;<?php endif; ?>
          <?= date('d M Y, h:i A', strtotime($a['created_at'])) ?>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:8px">
        <?php if (!$a['is_read']): ?>
        <a href="?read=<?= $a['alert_id'] ?>" style="font-size:.75rem;color:var(--primary);font-weight:700;text-decoration:none;white-space:nowrap">Mark Read</a>
        <?php else: ?><span style="font-size:.72rem;color:var(--text-muted);font-weight:600">Read</span><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="bcard"><div class="bcard-body"><div class="empty-state"><i class="bi bi-bell-slash"></i><p>No alerts yet. Great job managing your budget!</p></div></div></div>
    <?php endif; ?>
  </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
