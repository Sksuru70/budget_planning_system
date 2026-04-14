<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireLogin();
checkAndGenerateAlerts($pdo, uid());

$month = date('Y-m');
$uid   = uid();

// Totals this month
$stmt = $pdo->prepare("SELECT type, COALESCE(SUM(amount),0) AS total FROM transactions WHERE user_id=? AND DATE_FORMAT(transaction_date,'%Y-%m')=? GROUP BY type");
$stmt->execute([$uid, $month]);
$totals = ['income'=>0,'expense'=>0];
foreach ($stmt->fetchAll() as $r) $totals[$r['type']] = $r['total'];
$balance = $totals['income'] - $totals['expense'];

// Recent 5 transactions
$recent = $pdo->prepare("SELECT t.*,bc.category_name,bc.color FROM transactions t LEFT JOIN budget_categories bc ON bc.category_id=t.category_id WHERE t.user_id=? ORDER BY t.transaction_date DESC, t.created_at DESC LIMIT 5");
$recent->execute([$uid]);

// Budget categories with usage
$cats = $pdo->prepare("SELECT bc.*, COALESCE(SUM(t.amount),0) AS spent FROM budget_categories bc LEFT JOIN transactions t ON t.category_id=bc.category_id AND t.type='expense' AND DATE_FORMAT(t.transaction_date,'%Y-%m')=? WHERE bc.user_id=? AND bc.status='active' GROUP BY bc.category_id ORDER BY spent DESC LIMIT 6");
$cats->execute([$month, $uid]);
$catRows = $cats->fetchAll();

// Chart data
$chartLabels = $chartSpent = [];
foreach ($catRows as $c) { $chartLabels[] = $c['category_name']; $chartSpent[] = round($c['spent'],2); }

// Unread alerts count
$alertCnt = $pdo->prepare("SELECT COUNT(*) FROM alerts WHERE user_id=? AND is_read=0");
$alertCnt->execute([$uid]);
$unread = $alertCnt->fetchColumn();

// Savings summary
$savStmt = $pdo->prepare("SELECT COALESCE(SUM(saved_amount),0) AS total_saved, COALESCE(SUM(target_amount),0) AS total_target FROM savings_goals WHERE user_id=? AND status='in_progress'");
$savStmt->execute([$uid]);
$sav = $savStmt->fetch();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard – Budget Planning System</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
  <!-- Topbar -->
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:14px">
      <button id="sidebarToggle" style="display:none;background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--dark)"><i class="bi bi-list"></i></button>
      <span class="page-title">Dashboard</span>
    </div>
    <div class="user-info">
      <?php if ($unread > 0): ?>
      <a href="alerts.php" style="position:relative;color:var(--text-muted);text-decoration:none;font-size:1.2rem">
        <i class="bi bi-bell"></i>
        <span style="position:absolute;top:-4px;right:-4px;background:var(--danger);color:#fff;font-size:.6rem;font-weight:800;padding:1px 5px;border-radius:10px"><?= $unread ?></span>
      </a>
      <?php endif; ?>
      <div class="avatar"><?= strtoupper(substr($_SESSION['full_name'],0,1)) ?></div>
      <span style="font-weight:700;font-size:.9rem"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
    </div>
  </div>

  <div class="content-area">
    <?php if ($flash): ?><div class="flash-alert flash-<?= $flash['type'] ?>"><i class="bi bi-info-circle"></i><?= htmlspecialchars($flash['msg']) ?></div><?php endif; ?>

    <!-- Welcome -->
    <div style="margin-bottom:24px">
      <h4 style="font-weight:800;color:var(--dark)">Good <?= date('H')<12?'Morning':(date('H')<17?'Afternoon':'Evening') ?>, <?= htmlspecialchars(explode(' ',$_SESSION['full_name'])[0]) ?>! 👋</h4>
      <p style="color:var(--text-muted);font-size:.9rem"><?= date('l, d F Y') ?> &nbsp;|&nbsp; Here's your financial overview</p>
    </div>

    <!-- Stat Cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:18px;margin-bottom:28px">
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#4361ee22,#4361ee44)"><i class="bi bi-wallet2" style="color:#4361ee"></i></div>
        <div>
          <div class="label">Current Balance</div>
          <div class="value" style="color:<?= $balance>=0?'var(--success)':'var(--danger)' ?>"><?= fmt(abs($balance)) ?></div>
          <div class="change" style="color:var(--text-muted)">This Month</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#06d6a022,#06d6a044)"><i class="bi bi-arrow-down-circle" style="color:#06d6a0"></i></div>
        <div>
          <div class="label">Total Income</div>
          <div class="value" style="color:#06d6a0"><?= fmt($totals['income']) ?></div>
          <div class="change" style="color:var(--text-muted)">This Month</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#f7258522,#f7258544)"><i class="bi bi-arrow-up-circle" style="color:#f72585"></i></div>
        <div>
          <div class="label">Total Expenses</div>
          <div class="value" style="color:#f72585"><?= fmt($totals['expense']) ?></div>
          <div class="change" style="color:var(--text-muted)">This Month</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#7209b722,#7209b744)"><i class="bi bi-piggy-bank" style="color:#7209b7"></i></div>
        <div>
          <div class="label">Total Savings</div>
          <div class="value" style="color:#7209b7"><?= fmt($sav['total_saved']) ?></div>
          <div class="change" style="color:var(--text-muted)">of <?= fmt($sav['total_target']) ?> goal</div>
        </div>
      </div>
    </div>

    <!-- Charts + Budget -->
    <div style="display:grid;grid-template-columns:1fr 1.4fr;gap:20px;margin-bottom:24px">
      <!-- Doughnut -->
      <div class="bcard">
        <div class="bcard-header"><h6><i class="bi bi-pie-chart" style="color:var(--primary)"></i> Expense Distribution</h6></div>
        <div class="bcard-body" style="height:260px">
          <?php if (array_sum($chartSpent) > 0): ?>
          <canvas id="budgetChart"></canvas>
          <?php else: ?>
          <div class="empty-state"><i class="bi bi-pie-chart"></i><p>No expense data this month</p></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Budget progress -->
      <div class="bcard">
        <div class="bcard-header">
          <h6><i class="bi bi-bar-chart" style="color:var(--secondary)"></i> Budget Utilization</h6>
          <a href="budget.php" style="font-size:.8rem;color:var(--primary);font-weight:700;text-decoration:none">Manage →</a>
        </div>
        <div class="bcard-body">
          <?php if ($catRows): ?>
          <?php foreach ($catRows as $c):
            $pct  = $c['budget_limit'] > 0 ? min(100, ($c['spent']/$c['budget_limit'])*100) : 0;
            $color = $pct>=100?'var(--danger)':($pct>=80?'var(--warning)':'var(--primary)');
          ?>
          <div class="budget-item">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px">
              <span class="name"><i class="bi <?= $c['icon'] ?>" style="color:<?= $c['color'] ?>"></i> <?= htmlspecialchars($c['category_name']) ?></span>
              <span class="amounts"><?= fmt($c['spent']) ?> / <?= fmt($c['budget_limit']) ?></span>
            </div>
            <div class="progress"><div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $color ?>"></div></div>
          </div>
          <?php endforeach; ?>
          <?php else: ?>
          <div class="empty-state"><i class="bi bi-plus-circle"></i><p>No budget categories yet.<br><a href="budget.php">Add a category</a></p></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bcard">
      <div class="bcard-header">
        <h6><i class="bi bi-clock-history" style="color:var(--success)"></i> Recent Transactions</h6>
        <a href="transactions.php" style="font-size:.8rem;color:var(--primary);font-weight:700;text-decoration:none">View All →</a>
      </div>
      <div class="bcard-body" style="padding:0">
        <?php $rows = $recent->fetchAll(); if ($rows): ?>
        <table class="btable">
          <thead><tr><th>Date</th><th>Description</th><th>Category</th><th>Type</th><th style="text-align:right">Amount</th></tr></thead>
          <tbody>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td style="color:var(--text-muted);font-size:.82rem"><?= date('d M', strtotime($r['transaction_date'])) ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($r['description'] ?: '—') ?></td>
            <td><?php if ($r['category_name']): ?><span style="background:<?= $r['color'] ?>22;color:<?= $r['color'] ?>;padding:3px 9px;border-radius:20px;font-size:.72rem;font-weight:700"><?= htmlspecialchars($r['category_name']) ?></span><?php else: ?>—<?php endif; ?></td>
            <td><span class="badge-<?= $r['type'] ?>"><?= ucfirst($r['type']) ?></span></td>
            <td style="text-align:right;font-weight:700;color:<?= $r['type']==='income'?'#06d6a0':'#f72585' ?>"><?= ($r['type']==='income'?'+':'-') . fmt($r['amount']) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state"><i class="bi bi-receipt"></i><p>No transactions yet. <a href="transactions.php">Add one!</a></p></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
renderBudgetChart('budgetChart',
  <?= json_encode($chartLabels) ?>,
  <?= json_encode($chartSpent) ?>, []);
</script>
</body>
</html>
