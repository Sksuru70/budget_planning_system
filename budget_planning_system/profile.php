<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
requireLogin();
$uid = uid();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $name  = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone_number'] ?? '');
        if ($name) {
            $pdo->prepare("UPDATE users SET full_name=?,phone_number=? WHERE user_id=?")->execute([$name,$phone,$uid]);
            $_SESSION['full_name'] = $name;
            setFlash('success','Profile updated successfully!');
        }
    } elseif ($action === 'change_password') {
        $cur  = $_POST['current_password'] ?? '';
        $new  = $_POST['new_password'] ?? '';
        $conf = $_POST['confirm_password'] ?? '';
        $user = $pdo->prepare("SELECT password FROM users WHERE user_id=?");
        $user->execute([$uid]);
        $hash = $user->fetchColumn();
        if (!password_verify($cur,$hash)) { setFlash('danger','Current password is incorrect.'); }
        elseif (strlen($new) < 6) { setFlash('danger','New password must be at least 6 characters.'); }
        elseif ($new !== $conf) { setFlash('danger','New passwords do not match.'); }
        else {
            $pdo->prepare("UPDATE users SET password=? WHERE user_id=?")->execute([password_hash($new,PASSWORD_BCRYPT),$uid]);
            setFlash('success','Password changed successfully!');
        }
    }
    header('Location: profile.php'); exit;
}

$userStmt = $pdo->prepare("SELECT * FROM users WHERE user_id=?");
$userStmt->execute([$uid]);
$user = $userStmt->fetch();

// Stats
$totalTx   = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id=?"); $totalTx->execute([$uid]);
$totalCats = $pdo->prepare("SELECT COUNT(*) FROM budget_categories WHERE user_id=?"); $totalCats->execute([$uid]);
$totalGoals= $pdo->prepare("SELECT COUNT(*) FROM savings_goals WHERE user_id=?"); $totalGoals->execute([$uid]);

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Profile</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:14px">
      <button id="sidebarToggle" style="display:none;background:none;border:none;font-size:1.4rem;cursor:pointer"><i class="bi bi-list"></i></button>
      <span class="page-title">My Profile</span>
    </div>
  </div>
  <div class="content-area">
    <?php if ($flash): ?><div class="flash-alert flash-<?= $flash['type'] ?>"><i class="bi bi-info-circle"></i><?= htmlspecialchars($flash['msg']) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:300px 1fr;gap:24px;align-items:start">
      <!-- Left -->
      <div>
        <div class="bcard" style="margin-bottom:20px">
          <div class="bcard-body" style="text-align:center;padding:32px 22px">
            <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));display:inline-flex;align-items:center;justify-content:center;font-size:2rem;color:#fff;font-weight:800;margin-bottom:14px">
              <?= strtoupper(substr($user['full_name'],0,1)) ?>
            </div>
            <h5 style="font-weight:800;margin:0 0 4px"><?= htmlspecialchars($user['full_name']) ?></h5>
            <p style="color:var(--text-muted);font-size:.85rem;margin:0 0 12px"><?= htmlspecialchars($user['email']) ?></p>
            <span style="background:var(--primary)22;color:var(--primary);padding:4px 14px;border-radius:20px;font-size:.75rem;font-weight:700;text-transform:capitalize"><?= $user['role'] ?></span>
            <p style="color:var(--text-muted);font-size:.78rem;margin:14px 0 0">Member since <?= date('d M Y', strtotime($user['registration_date'])) ?></p>
          </div>
        </div>
        <div class="bcard">
          <div class="bcard-header"><h6>Activity Summary</h6></div>
          <div class="bcard-body">
            <?php foreach ([['bi-arrow-left-right','Transactions',$totalTx->fetchColumn(),'var(--primary)'],['bi-pie-chart','Budget Categories',$totalCats->fetchColumn(),'var(--secondary)'],['bi-piggy-bank','Savings Goals',$totalGoals->fetchColumn(),'var(--success)']] as [$ic,$lbl,$cnt,$col]): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border)">
              <span style="font-weight:600;font-size:.88rem"><i class="bi <?= $ic ?>" style="color:<?= $col ?>"></i> <?= $lbl ?></span>
              <span style="font-weight:800;color:var(--dark)"><?= $cnt ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Right -->
      <div>
        <div class="bcard" style="margin-bottom:20px">
          <div class="bcard-header"><h6><i class="bi bi-person" style="color:var(--primary)"></i> Edit Profile</h6></div>
          <div class="bcard-body">
            <form method="POST">
              <input type="hidden" name="action" value="update_profile">
              <div style="margin-bottom:16px"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required></div>
              <div style="margin-bottom:16px"><label class="form-label">Email Address</label><input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background:#f8faff;color:var(--text-muted)"></div>
              <div style="margin-bottom:22px"><label class="form-label">Phone Number</label><input type="text" name="phone_number" class="form-control" value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>" placeholder="10-digit mobile number"></div>
              <button type="submit" class="btn-primary-custom"><i class="bi bi-check-lg"></i> Update Profile</button>
            </form>
          </div>
        </div>
        <div class="bcard">
          <div class="bcard-header"><h6><i class="bi bi-shield-lock" style="color:var(--danger)"></i> Change Password</h6></div>
          <div class="bcard-body">
            <form method="POST">
              <input type="hidden" name="action" value="change_password">
              <div style="margin-bottom:16px"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
              <div style="margin-bottom:16px"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" required></div>
              <div style="margin-bottom:22px"><label class="form-label">Confirm New Password</label><input type="password" name="confirm_password" class="form-control" required></div>
              <button type="submit" class="btn-primary-custom" style="background:var(--danger)"><i class="bi bi-key"></i> Change Password</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
