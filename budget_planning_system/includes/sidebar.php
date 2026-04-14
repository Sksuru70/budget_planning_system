<?php // includes/sidebar.php – reusable sidebar
$page = basename($_SERVER['PHP_SELF']);
function navLink($href,$icon,$label,$page) {
    $active = basename($href) === $page ? ' active' : '';
    echo "<a href='$href' class='$active'><i class='bi $icon'></i> $label</a>";
}
?>
<nav class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="bi bi-wallet2"></i></div>
    <h5>BudgetPro</h5>
    <small>Smart Finance Manager</small>
  </div>
  <div class="sidebar-menu">
    <div class="nav-label">Main</div>
    <?php navLink('/budget_planning_system/dashboard.php','bi-speedometer2','Dashboard',$page); ?>
    <?php navLink('/budget_planning_system/transactions.php','bi-arrow-left-right','Transactions',$page); ?>
    <?php navLink('/budget_planning_system/budget.php','bi-pie-chart','Budget Categories',$page); ?>
    <?php navLink('/budget_planning_system/savings.php','bi-piggy-bank','Savings Goals',$page); ?>
    <div class="nav-label">Reports</div>
    <?php navLink('/budget_planning_system/reports.php','bi-bar-chart-line','Financial Reports',$page); ?>
    <?php navLink('/budget_planning_system/alerts.php','bi-bell','Alerts',$page); ?>
    <div class="nav-label">Account</div>
    <?php navLink('/budget_planning_system/profile.php','bi-person-circle','My Profile',$page); ?>
    <?php if (isAdmin()): ?>
    <?php navLink('/budget_planning_system/admin/index.php','bi-shield-check','Admin Panel',$page); ?>
    <?php endif; ?>
  </div>
  <div class="sidebar-footer">
    <a href="/budget_planning_system/logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </div>
</nav>
