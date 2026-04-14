<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';
requireAdmin();

$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalTx       = $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
$totalExpense  = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='expense'")->fetchColumn();
$totalGoals    = $pdo->query("SELECT COUNT(*) FROM savings_goals")->fetchColumn();

$users = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM transactions WHERE user_id=u.user_id) AS tx_count FROM users WHERE role='user' ORDER BY u.registration_date DESC");
$userRows = $users->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'toggle_user') {
    $uid  = (int)$_POST['user_id'];
    $pdo->prepare("UPDATE users SET is_active = 1 - is_active WHERE user_id=? AND role='user'")->execute([$uid]);
    setFlash('success','User status updated.');
    header('Location: index.php'); exit;
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Panel</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<!-- Admin Sidebar -->
<nav class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="bi bi-shield-check"></i></div>
    <h5>Admin Panel</h5>
    <small>BudgetPro</small>
  </div>
  <div class="sidebar-menu">
    <a href="index.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="users.php"><i class="bi bi-people"></i> Manage Users</a>
    <a href="../dashboard.php"><i class="bi bi-arrow-left"></i> Back to App</a>
  </div>
  <div class="sidebar-footer">
    <a href="../logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </div>
</nav>

<div class="main-content">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:14px">
      <button id="sidebarToggle" style="display:none;background:none;border:none;font-size:1.4rem;cursor:pointer"><i class="bi bi-list"></i></button>
      <span class="page-title">Admin Dashboard</span>
    </div>
    <div class="user-info">
      <div class="avatar" style="background:linear-gradient(135deg,#f72585,#7209b7)"><?= strtoupper(substr($_SESSION['full_name'],0,1)) ?></div>
      <span style="font-weight:700;font-size:.9rem"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
    </div>
  </div>
  <div class="content-area">
    <?php if ($flash): ?><div class="flash-alert flash-<?= $flash['type'] ?>"><i class="bi bi-info-circle"></i><?= htmlspecialchars($flash['msg']) ?></div><?php endif; ?>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:18px;margin-bottom:28px">
      <div class="stat-card"><div class="stat-icon" style="background:#dbeafe"><i class="bi bi-people" style="color:#2563eb"></i></div><div><div class="label">Total Users</div><div class="value"><?= $totalUsers ?></div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:#dcfce7"><i class="bi bi-receipt" style="color:#16a34a"></i></div><div><div class="label">Total Transactions</div><div class="value"><?= $totalTx ?></div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:#fee2e2"><i class="bi bi-cash" style="color:#dc2626"></i></div><div><div class="label">Total Expenses</div><div class="value" style="font-size:1.1rem"><?= fmt($totalExpense) ?></div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:#f3e8ff"><i class="bi bi-piggy-bank" style="color:#7209b7"></i></div><div><div class="label">Savings Goals</div><div class="value"><?= $totalGoals ?></div></div></div>
    </div>

    <!-- Users Table -->
    <div class="bcard">
      <div class="bcard-header"><h6><i class="bi bi-people" style="color:var(--primary)"></i> Registered Users</h6></div>
      <div class="bcard-body" style="padding:0">
        <table class="btable">
          <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th><th style="text-align:center">Transactions</th><th style="text-align:center">Status</th><th style="text-align:center">Action</th></tr></thead>
          <tbody>
          <?php foreach ($userRows as $u): ?>
          <tr>
            <td style="font-weight:700"><?= htmlspecialchars($u['full_name']) ?></td>
            <td style="color:var(--text-muted)"><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['phone_number'] ?: '—') ?></td>
            <td style="font-size:.82rem;color:var(--text-muted)"><?= date('d M Y', strtotime($u['registration_date'])) ?></td>
            <td style="text-align:center;font-weight:700"><?= $u['tx_count'] ?></td>
            <td style="text-align:center"><span style="padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;background:<?= $u['is_active']?'#dcfce7':'#fee2e2' ?>;color:<?= $u['is_active']?'#16a34a':'#dc2626' ?>"><?= $u['is_active']?'Active':'Inactive' ?></span></td>
            <td style="text-align:center">
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle_user">
                <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                <button type="submit" style="background:<?= $u['is_active']?'#fee2e2':'#dcfce7' ?>;color:<?= $u['is_active']?'#dc2626':'#16a34a' ?>;border:none;padding:5px 12px;border-radius:8px;cursor:pointer;font-weight:700;font-size:.78rem">
                  <?= $u['is_active']?'Deactivate':'Activate' ?>
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
