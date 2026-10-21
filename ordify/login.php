<?php
require 'config.php';
if (user()) redirect(home_for(user()['role']));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $q = db()->prepare('SELECT * FROM users WHERE email=?'); $q->execute([strtolower(trim($_POST['email'] ?? ''))]);
    $u = $q->fetch();
    if (!$u || !password_verify($_POST['password'] ?? '', $u['password'])) flash('Incorrect email or password.', 'error');
    elseif (!$u['email_verified']) flash('Please verify your email first.', 'error');
    elseif ($u['status'] === 'pending') flash('Your account is awaiting admin approval.', 'error');
    elseif ($u['status'] === 'suspended') flash('Your account has been suspended.', 'error');
    else {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $u['id'], 'name' => $u['name'], 'role' => $u['role']];
        redirect(home_for($u['role']));
    }
}
$title = 'Login'; require 'includes/header.php'; ?>
<div class="wrap"><form class="card auth" method="post">
  <h2>Welcome back</h2><?= csrf_field() ?>
  <label>Email</label><input type="email" name="email" required>
  <label>Password</label><input type="password" name="password" required>
  <button class="btn">Login</button>
  <p class="muted">New to ORDIFY? <a href="register.php">Create an account</a></p>
</form></div>
<?php require 'includes/footer.php'; ?>
