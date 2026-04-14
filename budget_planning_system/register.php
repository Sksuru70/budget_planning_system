<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit; }

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['full_name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$name || !$email || !$pass) { $error = 'Name, email and password are required.'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Invalid email address.'; }
    elseif (strlen($pass) < 6) { $error = 'Password must be at least 6 characters.'; }
    elseif ($pass !== $confirm) { $error = 'Passwords do not match.'; }
    else {
        $chk = $pdo->prepare("SELECT user_id FROM users WHERE email=?");
        $chk->execute([$email]);
        if ($chk->fetch()) { $error = 'Email already registered. Please login.'; }
        else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $ins  = $pdo->prepare("INSERT INTO users (full_name,email,phone_number,password) VALUES (?,?,?,?)");
            $ins->execute([$name, $email, $phone, $hash]);
            $success = 'Account created successfully! You can now login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Register – Budget Planning System</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card" style="max-width:480px">
    <div class="auth-logo">
      <div class="icon"><i class="bi bi-wallet2"></i></div>
      <h4>BudgetPro</h4>
    </div>
    <h2>Create Account</h2>
    <p class="subtitle">Start planning your finances today</p>
    <?php if ($error): ?><div class="flash-alert flash-danger"><i class="bi bi-x-circle"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="flash-alert flash-success"><i class="bi bi-check-circle"></i><?= $success ?></div><?php endif; ?>
    <form method="POST">
      <div style="margin-bottom:14px">
        <label class="form-label">Full Name</label>
        <div class="input-group-icon"><i class="bi bi-person"></i>
          <input type="text" name="full_name" class="form-control" placeholder="Your full name" required value="<?= htmlspecialchars($_POST['full_name']??'') ?>">
        </div>
      </div>
      <div style="margin-bottom:14px">
        <label class="form-label">Email Address</label>
        <div class="input-group-icon"><i class="bi bi-envelope"></i>
          <input type="email" name="email" class="form-control" placeholder="you@example.com" required value="<?= htmlspecialchars($_POST['email']??'') ?>">
        </div>
      </div>
      <div style="margin-bottom:14px">
        <label class="form-label">Phone Number (Optional)</label>
        <div class="input-group-icon"><i class="bi bi-phone"></i>
          <input type="text" name="phone" class="form-control" placeholder="10-digit mobile" value="<?= htmlspecialchars($_POST['phone']??'') ?>">
        </div>
      </div>
      <div style="margin-bottom:14px">
        <label class="form-label">Password</label>
        <div class="input-group-icon"><i class="bi bi-lock"></i>
          <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
        </div>
      </div>
      <div style="margin-bottom:22px">
        <label class="form-label">Confirm Password</label>
        <div class="input-group-icon"><i class="bi bi-lock-fill"></i>
          <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
        </div>
      </div>
      <button type="submit" class="btn-primary-custom" style="width:100%;justify-content:center;padding:12px">
        <i class="bi bi-person-plus"></i> Create Account
      </button>
    </form>
    <p style="text-align:center;margin-top:18px;font-size:.88rem;color:var(--text-muted)">
      Already have an account? <a href="index.php" style="color:var(--primary);font-weight:700">Sign in</a>
    </p>
  </div>
</div>
</body>
</html>
