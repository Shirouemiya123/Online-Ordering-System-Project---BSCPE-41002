<?php
require 'config.php';
if (user()) redirect(home_for(user()['role']));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? ''; $role = $_POST['role'] ?? 'customer';
    if (!in_array($role, ['customer', 'owner', 'courier'], true)) $role = 'customer';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) flash('Enter a valid name and email.', 'error');
    elseif (strlen($pass) < 8) flash('Password must be at least 8 characters.', 'error');
    else {
        $q = db()->prepare('SELECT id FROM users WHERE email=?'); $q->execute([$email]);
        if ($q->fetch()) flash('That email is already registered.', 'error');
        else {
            $token = bin2hex(random_bytes(32));
            $status = $role === 'customer' ? 'active' : 'pending'; // owners & couriers need admin approval
            db()->prepare('INSERT INTO users(name,email,password,role,status,verify_token) VALUES(?,?,?,?,?,?)')
                ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role, $status, $token]);
            $link = APP_URL . '/verify.php?token=' . $token;
            send_verification($email, $name, $link);
            flash('Account created! Check your email to verify. (Dev mode: see storage/mail.log)');
            redirect('login.php');
        }
    }
}
$title = 'Create Account'; require 'includes/header.php'; ?>
<div class="wrap"><form class="card auth" method="post">
  <h2>Create your account</h2><?= csrf_field() ?>
  <label>Full name</label><input name="name" required value="<?= e($_POST['name'] ?? '') ?>">
  <label>Email</label><input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
  <label>Password (min. 8 characters)</label><input type="password" name="password" required>
  <label>I want to join as</label>
  <select name="role"><option value="customer">Customer</option><option value="owner">Business Owner</option><option value="courier">Courier</option></select>
  <button class="btn">Sign Up</button>
  <p class="muted">Already have an account? <a href="login.php">Login</a></p>
</form></div>
<?php require 'includes/footer.php'; ?>
