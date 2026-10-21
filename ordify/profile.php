<?php
require 'config.php'; require_role('admin', 'customer', 'owner', 'courier');
$uid = user()['id'];
$q = db()->prepare('SELECT * FROM users WHERE id=?'); $q->execute([$uid]); $me = $q->fetch();
if (!$me) { session_destroy(); redirect('login.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['do'] ?? '') === 'name') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '' || mb_strlen($name) > 100) flash('Enter your name (max 100 characters).', 'error');
        else { db()->prepare('UPDATE users SET name=? WHERE id=?')->execute([$name, $uid]); $_SESSION['user']['name'] = $name; flash('Profile updated.'); }
    } elseif (($_POST['do'] ?? '') === 'password') {
        $new = $_POST['new'] ?? '';
        if (!password_verify($_POST['current'] ?? '', $me['password'])) flash('Your current password is incorrect.', 'error');
        elseif (strlen($new) < 8) flash('New password must be at least 8 characters.', 'error');
        elseif ($new !== ($_POST['confirm'] ?? '')) flash('The new passwords do not match.', 'error');
        else { db()->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $uid]); session_regenerate_id(true); flash('Password changed.'); }
    }
    redirect('profile.php');
}
$title = 'My Profile'; require 'includes/header.php'; ?>
<div class="wrap" style="display:grid;gap:24px;justify-items:center;padding-bottom:40px">
<form class="card auth form" method="post"><h2>My Profile</h2><?= csrf_field() ?><input type="hidden" name="do" value="name">
  <label>Full name</label><input name="name" required maxlength="100" value="<?= e($me['name']) ?>">
  <label>Email</label><input value="<?= e($me['email']) ?>" disabled>
  <label>Role</label><input value="<?= e(ucfirst($me['role'])) ?>" disabled>
  <button class="btn">Save changes</button></form>
<form class="card auth form" method="post"><h2>Change password</h2><?= csrf_field() ?><input type="hidden" name="do" value="password">
  <label>Current password</label><input type="password" name="current" required autocomplete="current-password">
  <label>New password (min. 8 characters)</label><input type="password" name="new" required minlength="8" autocomplete="new-password">
  <label>Confirm new password</label><input type="password" name="confirm" required minlength="8" autocomplete="new-password">
  <button class="btn">Update password</button></form>
</div>
<?php require 'includes/footer.php'; ?>
