<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireLogin();
$uid = uid();

// Last 6 months data for bar chart
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $m    = date('Y-m', strtotime("-$i months"));
    $lbl  = date('M Y', strtotime("-$i months"));
    $stmt = $pdo->prepare("SELECT type, COALESCE(SUM(amount),0) AS total FROM transactions WHERE user_id=? AND DATE_FORMAT(transaction_date,'%Y-%m')=? GROUP BY type");
    $stmt->execute([$uid,$m]);
    $row = ['income'=>0,'expense'=>0,'label'=>$lbl];
    foreach ($stmt->fetchAll() as $r) $row[$r['type']] = (float)$r['total'];
    $monthlyData[] = $row;
}

// Category breakdown for selected month
$selMonth = $_GET['month'] ?? date('Y-m');
$catBreak = $pdo->prepare("SELECT bc.category_name, bc.color, COALESCE(SUM(t.amount),0) AS total FROM budget_categories bc LEFT JOIN transactions t ON t.category_id=bc.category_id AND t.type='expense' AND DATE_FORMAT(t.transaction_date,'%Y-%m')=? WHERE bc.user_id=? GROUP BY bc.category_id ORDER BY total DESC");
$catBreak->execute([$selMonth, $uid]);
$catRows = $catBreak->fetchAll();

// Full category report with budget comparison
$catReport = $pdo->prepare("SELECT bc.category_name, bc.budget_limit, bc.color, bc.icon, COALESCE(SUM(t.amount),0) AS spent FROM budget_categories bc LEFT JOIN transactions t ON t.category_id=bc.category_id AND t.type='expense' AND DATE_FORMAT(t.transaction_date,'%Y-%m')=? WHERE bc.user_id=? GROUP BY bc.category_id ORDER BY spent DESC");
$catReport->execute([$selMonth, $uid]);
$catReportRows = $catReport->fetchAll();

// Monthly totals this month
$month = date('Y-m');
$sumStmt = $pdo->prepare("SELECT type, COALESCE(SUM(amount),0) AS total FROM transactions WHERE user_id=? AND DATE_FORMAT(transaction_date,'%Y-%m')=? GROUP BY type");
$sumStmt->execute([$uid,$month]);
$totals = ['income'=>0,'expense'=>0];
foreach ($sumStmt->fetchAll() as $r) $totals[$r['type']] = $r['total'];

// Savings progress over 6 months (use current saved_amount as approximation)
$savGoals = $pdo->prepare("SELECT goal_name, saved_amount, target_amount FROM savings_goals WHERE user_id=? AND status='in_progress' ORDER BY created_at DESC LIMIT 1");
$savGoals->execute([$uid]);
$topGoal = $savGoals->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Financial Reports</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:14px">
      <button id="sidebarToggle" style="display:none;background:none;border:none;font-size:1.4rem;cursor:pointer"><i class="bi bi-list"></i></button>
      <span class="page-title">Financial Reports</span>
    </div>
    <form method="GET" style="display:flex;gap:10px;align-items:center">
      <input type="month" name="month" value="<?= htmlspecialchars($selMonth) ?>" class="form-control" style="width:150px">
      <button type="submit" class="btn-primary-custom" style="padding:9px 18px"><i class="bi bi-filter"></i> View</button>
    </form>
  </div>
  <div class="content-area">
    <!-- KPI Row -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px">
      <div class="stat-card"><div class="stat-icon" style="background:#dcfce7"><i class="bi bi-arrow-down-circle" style="color:#16a34a"></i></div><div><div class="label">Monthly Income</div><div class="value" style="color:#06d6a0"><?= fmt($totals['income']) ?></div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:#fee2e2"><i class="bi bi-arrow-up-circle" style="color:#dc2626"></i></div><div><div class="label">Monthly Expenses</div><div class="value" style="color:#f72585"><?= fmt($totals['expense']) ?></div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:#dbeafe"><i class="bi bi-graph-up" style="color:#2563eb"></i></div><div><div class="label">Savings Rate</div><div class="value"><?= $totals['income']>0?round((($totals['income']-$totals['expense'])/$totals['income'])*100,1):0 ?>%</div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:#f3e8ff"><i class="bi bi-balance-scale" style="color:#7209b7"></i></div><div><div class="label">Net Flow</div><div class="value" style="color:<?= $totals['income']-$totals['expense']>=0?'var(--success)':'var(--danger)' ?>"><?= fmt(abs($totals['income']-$totals['expense'])) ?></div></div></div>
    </div>

    <!-- Charts Row -->
    <div style="display:grid;grid-template-columns:1.6fr 1fr;gap:20px;margin-bottom:24px">
      <!-- Bar chart -->
      <div class="bcard">
        <div class="bcard-header"><h6><i class="bi bi-bar-chart" style="color:var(--primary)"></i> Income vs Expense (Last 6 Months)</h6></div>
        <div class="bcard-body" style="height:280px"><canvas id="monthlyChart"></canvas></div>
      </div>
      <!-- Doughnut -->
      <div class="bcard">
        <div class="bcard-header"><h6><i class="bi bi-pie-chart" style="color:var(--secondary)"></i> Expense by Category (<?= date('M Y',strtotime($selMonth.'-01')) ?>)</h6></div>
        <div class="bcard-body" style="height:280px">
          <?php if (array_sum(array_column($catRows,'total')) > 0): ?>
          <canvas id="catChart"></canvas>
          <?php else: ?>
          <div class="empty-state"><i class="bi bi-pie-chart"></i><p>No expense data for this month</p></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Category Report Table -->
    <div class="bcard" style="margin-bottom:24px">
      <div class="bcard-header"><h6><i class="bi bi-table" style="color:var(--success)"></i> Category-wise Budget Report — <?= date('F Y',strtotime($selMonth.'-01')) ?></h6></div>
      <div class="bcard-body" style="padding:0">
        <?php if ($catReportRows): ?>
        <table class="btable">
          <thead><tr><th>Category</th><th style="text-align:right">Budget</th><th style="text-align:right">Spent</th><th style="text-align:right">Remaining</th><th>Utilization</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($catReportRows as $r):
            $pct = $r['budget_limit']>0 ? min(100,($r['spent']/$r['budget_limit'])*100) : 0;
            $rem = max(0,$r['budget_limit']-$r['spent']);
            $status = $pct>=100?'Over Budget':($pct>=80?'Near Limit':'On Track');
            $stcolor = $pct>=100?'var(--danger)':($pct>=80?'#d97706':'#16a34a');
          ?>
          <tr>
            <td><span style="display:flex;align-items:center;gap:8px"><div style="width:8px;height:8px;border-radius:50%;background:<?= $r['color'] ?>"></div><?= htmlspecialchars($r['category_name']) ?></span></td>
            <td style="text-align:right;font-weight:600"><?= fmt($r['budget_limit']) ?></td>
            <td style="text-align:right;font-weight:600;color:#f72585"><?= fmt($r['spent']) ?></td>
            <td style="text-align:right;font-weight:600;color:#06d6a0"><?= fmt($rem) ?></td>
            <td style="min-width:120px">
              <div style="display:flex;align-items:center;gap:8px">
                <div class="progress" style="flex:1"><div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $stcolor ?>"></div></div>
                <span style="font-size:.78rem;font-weight:700;color:var(--text-muted);white-space:nowrap"><?= round($pct) ?>%</span>
              </div>
            </td>
            <td><span style="padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;background:<?= $stcolor ?>22;color:<?= $stcolor ?>"><?= $status ?></span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state"><i class="bi bi-table"></i><p>No category data available for this month.</p></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- 6-month summary table -->
    <div class="bcard">
      <div class="bcard-header"><h6><i class="bi bi-calendar3" style="color:var(--info)"></i> 6-Month Financial Summary</h6></div>
      <div class="bcard-body" style="padding:0">
        <table class="btable">
          <thead><tr><th>Month</th><th style="text-align:right">Income</th><th style="text-align:right">Expenses</th><th style="text-align:right">Net</th><th>Savings Rate</th></tr></thead>
          <tbody>
          <?php foreach ($monthlyData as $m): $net = $m['income']-$m['expense']; $rate = $m['income']>0?round(($net/$m['income'])*100,1):0; ?>
          <tr>
            <td style="font-weight:700"><?= $m['label'] ?></td>
            <td style="text-align:right;color:#06d6a0;font-weight:600"><?= fmt($m['income']) ?></td>
            <td style="text-align:right;color:#f72585;font-weight:600"><?= fmt($m['expense']) ?></td>
            <td style="text-align:right;font-weight:700;color:<?= $net>=0?'#06d6a0':'#f72585' ?>"><?= ($net>=0?'+':'') . fmt($net) ?></td>
            <td><span style="font-weight:700;color:<?= $rate>=0?'#16a34a':'#dc2626' ?>"><?= $rate ?>%</span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
const md = <?= json_encode($monthlyData) ?>;
renderMonthlyChart('monthlyChart', md.map(m=>m.label), md.map(m=>m.income), md.map(m=>m.expense));
renderBudgetChart('catChart',
  <?= json_encode(array_column($catRows,'category_name')) ?>,
  <?= json_encode(array_map(fn($r)=>(float)$r['total'],$catRows)) ?>, []);
</script>
</body>
</html>
