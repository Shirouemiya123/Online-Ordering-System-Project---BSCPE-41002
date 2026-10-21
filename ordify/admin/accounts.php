<?php
require '../config.php'; require_role('admin');
$allowedRoles = ['admin','customer','owner','courier'];
$allowedStatuses = ['active','pending','suspended'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
    $stmt = db()->prepare('SELECT id, name, email, role, status FROM users WHERE id=?');
    $stmt->execute([$id]);
    $target = $stmt->fetch();

    if (!$target) {
        flash('Account not found.', 'error');
    } elseif ($action === 'save') {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $role = $_POST['role'] ?? '';
        $status = $_POST['status'] ?? '';
        if ($name === '' || mb_strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150 || !in_array($role, $allowedRoles, true) || !in_array($status, $allowedStatuses, true)) {
            flash('Please enter valid account details.', 'error');
        } elseif ($id === (int)user()['id'] && ($role !== 'admin' || $status !== 'active')) {
            flash('You cannot remove your own administrator access or suspend your own account.', 'error');
        } else {
            $dupe = db()->prepare('SELECT id FROM users WHERE email=? AND id<>?');
            $dupe->execute([$email, $id]);
            if ($dupe->fetch()) {
                flash('That email address is already used by another account.', 'error');
            } else {
                if ($target['role'] === 'admin' && ($role !== 'admin' || $status !== 'active')) {
                    $admins = db()->query("SELECT COUNT(*) FROM users WHERE role='admin' AND status='active'")->fetchColumn();
                    if ((int)$admins <= 1) {
                        flash('The last active administrator cannot be demoted or suspended.', 'error');
                        redirect('admin/accounts.php');
                    }
                }
                db()->prepare('UPDATE users SET name=?, email=?, role=?, status=? WHERE id=?')->execute([$name, $email, $role, $status, $id]);
                flash('Account updated successfully.');
            }
        }
    } elseif ($action === 'reset_password') {
        $temporary = bin2hex(random_bytes(5)) . 'A9!';
        db()->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($temporary, PASSWORD_DEFAULT), $id]);
        flash('Temporary password for ' . $target['email'] . ': ' . $temporary . ' — copy it now and share it securely.');
    } else {
        flash('Unknown action.', 'error');
    }
    redirect('admin/accounts.php');
}

$q = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$sql = 'SELECT id,name,email,role,status,email_verified,created_at FROM users WHERE 1=1';
$args = [];
if ($q !== '') { $sql .= ' AND (name LIKE ? OR email LIKE ? OR CAST(id AS CHAR)=?)'; $args[]='%'.$q.'%'; $args[]='%'.$q.'%'; $args[]=$q; }
if (in_array($roleFilter, $allowedRoles, true)) { $sql .= ' AND role=?'; $args[]=$roleFilter; }
if (in_array($statusFilter, $allowedStatuses, true)) { $sql .= ' AND status=?'; $args[]=$statusFilter; }
$sql .= ' ORDER BY id DESC LIMIT 300';
$stmt = db()->prepare($sql); $stmt->execute($args); $accounts = $stmt->fetchAll();
$title = 'Account Management'; require '../includes/header.php'; ?>
<div class="wrap dash">
  <h1>Account Management</h1>
  <p class="muted" style="text-align:left">Manage account details, roles, access status, and password resets. Changes take effect immediately.</p>
  <p style="margin:18px 0"><a class="btn btn-sm" href="index.php">← Admin Dashboard</a></p>
  <form class="card" method="get" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin:18px 0">
    <div style="flex:2;min-width:200px"><label for="q">Search name, email, or ID</label><input id="q" name="q" value="<?= e($q) ?>" placeholder="Search accounts"></div>
    <div><label for="role">Role</label><select id="role" name="role"><option value="">All roles</option><?php foreach ($allowedRoles as $r): ?><option value="<?= e($r) ?>" <?= $roleFilter===$r?'selected':'' ?>><?= e(ucfirst($r)) ?></option><?php endforeach; ?></select></div>
    <div><label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option><?php foreach ($allowedStatuses as $s): ?><option value="<?= e($s) ?>" <?= $statusFilter===$s?'selected':'' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select></div>
    <button class="btn btn-sm">Search</button><a class="btn btn-sm" href="accounts.php">Clear</a>
  </form>
  <p class="muted" style="text-align:left">Showing up to 300 accounts.</p>
  <?php foreach ($accounts as $a): ?>
  <section class="card" style="margin:14px 0;padding:18px">
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:12px">
      <div><strong>#<?= (int)$a['id'] ?> · <?= e($a['name']) ?></strong><div class="muted" style="text-align:left;font-size:.9rem"><?= e($a['email']) ?></div></div>
      <div><span class="tag"><?= e(ucfirst($a['role'])) ?></span> <span class="tag <?= $a['status']==='suspended'?'off':'' ?>"><?= e(ucfirst($a['status'])) ?></span> <?= $a['email_verified'] ? '<span class="muted">Email verified</span>' : '<span class="muted">Email not verified</span>' ?></div>
    </div>
    <form method="post" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;align-items:end">
      <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
      <div><label>Name</label><input name="name" maxlength="100" required value="<?= e($a['name']) ?>"></div>
      <div><label>Email</label><input type="email" name="email" maxlength="150" required value="<?= e($a['email']) ?>"></div>
      <div><label>Role</label><select name="role"><?php foreach ($allowedRoles as $r): ?><option value="<?= e($r) ?>" <?= $a['role']===$r?'selected':'' ?>><?= e(ucfirst($r)) ?></option><?php endforeach; ?></select></div>
      <div><label>Status</label><select name="status"><?php foreach ($allowedStatuses as $s): ?><option value="<?= e($s) ?>" <?= $a['status']===$s?'selected':'' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select></div>
      <div><button class="btn btn-sm" type="submit">Save changes</button></div>
    </form>
    <form method="post" style="margin-top:10px" onsubmit="return confirm('Reset this account password? The current password will stop working.');">
      <?= csrf_field() ?><input type="hidden" name="action" value="reset_password"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="btn btn-sm" type="submit">Generate temporary password</button>
    </form>
    <small class="muted">Created: <?= e($a['created_at']) ?></small>
  </section>
  <?php endforeach; ?>
  <?php if (!$accounts): ?><div class="card">No accounts match your search.</div><?php endif; ?>
</div>
<?php require '../includes/footer.php'; ?>
