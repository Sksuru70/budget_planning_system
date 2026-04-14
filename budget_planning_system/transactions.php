<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireLogin();
$uid = uid();

// Handle add/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $amount   = (float)($_POST['amount'] ?? 0);
        $type     = $_POST['type'] ?? '';
        $catId    = ($_POST['category_id'] ?? '') ?: null;
        $desc     = trim($_POST['description'] ?? '');
        $date     = $_POST['transaction_date'] ?? date('Y-m-d');
        if ($amount > 0 && in_array($type, ['income','expense'])) {
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id,category_id,amount,type,description,transaction_date) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$uid, $catId, $amount, $type, $desc, $date]);
            checkAndGenerateAlerts($pdo, $uid);
            setFlash('success','Transaction added successfully!');
        } else { setFlash('danger','Please provide a valid amount and type.'); }
    } elseif ($action === 'delete') {
        $tid = (int)($_POST['transaction_id'] ?? 0);
        $pdo->prepare("DELETE FROM transactions WHERE transaction_id=? AND user_id=?")->execute([$tid, $uid]);
        setFlash('success','Transaction deleted.');
    }
    header('Location: transactions.php'); exit;
}

// Filters
$filterType  = $_GET['type']  ?? '';
$filterMonth = $_GET['month'] ?? date('Y-m');
$filterCat   = $_GET['cat']   ?? '';

$where = "WHERE t.user_id=:uid";
$params = [':uid' => $uid];
if ($filterType) { $where .= " AND t.type=:type"; $params[':type'] = $filterType; }
if ($filterMonth) { $where .= " AND DATE_FORMAT(t.transaction_date,'%Y-%m')=:month"; $params[':month'] = $filterMonth; }
if ($filterCat) { $where .= " AND t.category_id=:cat"; $params[':cat'] = $filterCat; }

$transactions = $pdo->prepare("SELECT t.*,bc.category_name,bc.color,bc.icon FROM transactions t LEFT JOIN budget_categories bc ON bc.category_id=t.category_id $where ORDER BY t.transaction_date DESC, t.created_at DESC");
$transactions->execute($params);
$txRows = $transactions->fetchAll();

// Categories for dropdown
$catList = $pdo->prepare("SELECT * FROM budget_categories WHERE user_id=? AND status='active' ORDER BY category_name");
$catList->execute([$uid]);
$categories = $catList->fetchAll();

// Monthly totals
$totStmt = $pdo->prepare("SELECT type,COALESCE(SUM(amount),0) AS total FROM transactions WHERE user_id=? AND DATE_FORMAT(transaction_date,'%Y-%m')=? GROUP BY type");
$totStmt->execute([$uid, $filterMonth]);
$totals = ['income'=>0,'expense'=>0];
foreach ($totStmt->fetchAll() as $r) $totals[$r['type']] = $r['total'];

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Transactions – Budget Planning System</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:14px">
      <button id="sidebarToggle" style="display:none;background:none;border:none;font-size:1.4rem;cursor:pointer"><i class="bi bi-list"></i></button>
      <span class="page-title">Transactions</span>
    </div>
    <button onclick="document.getElementById('addModal').style.display='flex'" class="btn-primary-custom"><i class="bi bi-plus-lg"></i> Add Transaction</button>
  </div>
  <div class="content-area">
    <?php if ($flash): ?><div class="flash-alert flash-<?= $flash['type'] ?>"><i class="bi bi-info-circle"></i><?= htmlspecialchars($flash['msg']) ?></div><?php endif; ?>

    <!-- Monthly summary -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px">
      <div class="stat-card"><div class="stat-icon" style="background:#dcfce7"><i class="bi bi-arrow-down-circle" style="color:#16a34a"></i></div><div><div class="label">Income</div><div class="value" style="color:#06d6a0"><?= fmt($totals['income']) ?></div><div class="change" style="color:var(--text-muted)"><?= date('M Y',strtotime($filterMonth.'-01')) ?></div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:#fee2e2"><i class="bi bi-arrow-up-circle" style="color:#dc2626"></i></div><div><div class="label">Expenses</div><div class="value" style="color:#f72585"><?= fmt($totals['expense']) ?></div><div class="change" style="color:var(--text-muted)"><?= date('M Y',strtotime($filterMonth.'-01')) ?></div></div></div>
      <div class="stat-card"><div class="stat-icon" style="background:#dbeafe"><i class="bi bi-wallet2" style="color:#2563eb"></i></div><div><div class="label">Net Balance</div><div class="value" style="color:<?= $totals['income']-$totals['expense']>=0?'var(--success)':'var(--danger)' ?>"><?= fmt(abs($totals['income']-$totals['expense'])) ?></div><div class="change" style="color:var(--text-muted)"><?= $totals['income']-$totals['expense']>=0?'Surplus':'Deficit' ?></div></div></div>
    </div>

    <!-- Filters -->
    <div class="bcard" style="margin-bottom:20px">
      <div class="bcard-body" style="padding:16px">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
          <div>
            <label class="form-label">Month</label>
            <input type="month" name="month" value="<?= htmlspecialchars($filterMonth) ?>" class="form-control" style="width:160px">
          </div>
          <div>
            <label class="form-label">Type</label>
            <select name="type" class="form-select" style="width:130px">
              <option value="">All Types</option>
              <option value="income" <?= $filterType==='income'?'selected':'' ?>>Income</option>
              <option value="expense" <?= $filterType==='expense'?'selected':'' ?>>Expense</option>
            </select>
          </div>
          <div>
            <label class="form-label">Category</label>
            <select name="cat" class="form-select" style="width:160px">
              <option value="">All Categories</option>
              <?php foreach ($categories as $c): ?>
              <option value="<?= $c['category_id'] ?>" <?= $filterCat==$c['category_id']?'selected':'' ?>><?= htmlspecialchars($c['category_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn-primary-custom"><i class="bi bi-funnel"></i> Filter</button>
          <a href="transactions.php" class="btn-outline-custom">Reset</a>
        </form>
      </div>
    </div>

    <!-- Transactions Table -->
    <div class="bcard">
      <div class="bcard-header"><h6><i class="bi bi-list-ul" style="color:var(--primary)"></i> Transaction History (<?= count($txRows) ?> records)</h6></div>
      <div class="bcard-body" style="padding:0">
        <?php if ($txRows): ?>
        <table class="btable">
          <thead><tr><th>Date</th><th>Description</th><th>Category</th><th>Type</th><th style="text-align:right">Amount</th><th style="text-align:center">Action</th></tr></thead>
          <tbody>
          <?php foreach ($txRows as $r): ?>
          <tr>
            <td style="color:var(--text-muted);font-size:.82rem;white-space:nowrap"><?= date('d M Y', strtotime($r['transaction_date'])) ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($r['description'] ?: '—') ?></td>
            <td><?php if ($r['category_name']): ?><span style="background:<?= $r['color'] ?>22;color:<?= $r['color'] ?>;padding:3px 9px;border-radius:20px;font-size:.72rem;font-weight:700"><i class="bi <?= $r['icon'] ?>"></i> <?= htmlspecialchars($r['category_name']) ?></span><?php else: ?>—<?php endif; ?></td>
            <td><span class="badge-<?= $r['type'] ?>"><?= ucfirst($r['type']) ?></span></td>
            <td style="text-align:right;font-weight:700;color:<?= $r['type']==='income'?'#06d6a0':'#f72585' ?>"><?= ($r['type']==='income'?'+':'-') . fmt($r['amount']) ?></td>
            <td style="text-align:center">
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="transaction_id" value="<?= $r['transaction_id'] ?>">
                <button type="submit" class="btn-danger-custom" data-confirm="Delete this transaction?" style="padding:4px 10px;font-size:.75rem"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state"><i class="bi bi-receipt"></i><p>No transactions found for the selected filters.</p></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Add Transaction Modal -->
<div id="addModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:20px;padding:32px;width:100%;max-width:460px;margin:20px;box-shadow:0 24px 64px rgba(0,0,0,.3)">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px">
      <h5 style="font-weight:800;margin:0">Add Transaction</h5>
      <button onclick="document.getElementById('addModal').style.display='none'" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--text-muted)">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div style="margin-bottom:14px">
        <label class="form-label">Type</label>
        <div style="display:flex;gap:10px">
          <label style="flex:1;display:flex;align-items:center;gap:8px;padding:10px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;font-weight:600">
            <input type="radio" name="type" value="income" required> <i class="bi bi-arrow-down-circle" style="color:#06d6a0"></i> Income
          </label>
          <label style="flex:1;display:flex;align-items:center;gap:8px;padding:10px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;font-weight:600">
            <input type="radio" name="type" value="expense"> <i class="bi bi-arrow-up-circle" style="color:#f72585"></i> Expense
          </label>
        </div>
      </div>
      <div style="margin-bottom:14px">
        <label class="form-label">Amount (₹)</label>
        <input type="number" name="amount" class="form-control" placeholder="0.00" step="0.01" min="0.01" required>
      </div>
      <div style="margin-bottom:14px">
        <label class="form-label">Category (optional)</label>
        <select name="category_id" class="form-select">
          <option value="">-- Select Category --</option>
          <?php foreach ($categories as $c): ?>
          <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="margin-bottom:14px">
        <label class="form-label">Description</label>
        <input type="text" name="description" class="form-control" placeholder="e.g. Grocery shopping">
      </div>
      <div style="margin-bottom:22px">
        <label class="form-label">Date</label>
        <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
      </div>
      <button type="submit" class="btn-primary-custom" style="width:100%;justify-content:center;padding:12px">
        <i class="bi bi-plus-lg"></i> Add Transaction
      </button>
    </form>
  </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
