<?php
// index.php  –  Login Page
require_once 'includes/functions.php';
require_once 'includes/db.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/index.php' : 'dashboard.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['role']      = $user['role'];
            header('Location: ' . ($user['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php'));
            exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login – Budget Planning System</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="icon"><i class="bi bi-wallet2"></i></div>
      <h4>BudgetPro</h4>
      <p>Smart Budget Planning System</p>
    </div>
    <h2>Welcome back 👋</h2>
    <p class="subtitle">Sign in to manage your finances</p>

    <?php if ($error): ?>
      <div class="flash-alert flash-danger"><i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <div style="margin-bottom:16px">
        <label class="form-label">Email Address</label>
        <div class="input-group-icon">
          <i class="bi bi-envelope"></i>
          <input type="email" name="email" class="form-control" placeholder="you@example.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
      </div>
      <div style="margin-bottom:22px">
        <label class="form-label">Password</label>
        <div class="input-group-icon">
          <i class="bi bi-lock"></i>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
      </div>
      <button type="submit" class="btn-primary-custom" style="width:100%;justify-content:center;padding:12px">
        <i class="bi bi-box-arrow-in-right"></i> Sign In
      </button>
    </form>

    <p style="text-align:center;margin-top:20px;font-size:.88rem;color:var(--text-muted)">
      Don't have an account? <a href="register.php" style="color:var(--primary);font-weight:700">Register here</a>
    </p>
    <div style="margin-top:18px;padding:14px;background:#f8faff;border-radius:var(--radius-sm);font-size:.78rem;color:var(--text-muted)">
      <strong>Demo Credentials:</strong><br>
      User: demo@budget.com / demo@123<br>
      Admin: admin@budget.com / admin@123
    </div>
  </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
