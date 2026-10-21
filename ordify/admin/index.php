<?php
require '../config.php'; require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    db()->prepare("UPDATE users SET status='active' WHERE id=? AND role IN ('owner','courier')")->execute([(int)$_POST['id']]);
    redirect('admin/index.php');
}
$count = fn($r) => db()->query("SELECT COUNT(*) FROM users WHERE role='$r'")->fetchColumn();
$pending = db()->query("SELECT * FROM users WHERE status='pending' ORDER BY created_at")->fetchAll();
$title = 'Admin'; require '../includes/header.php'; ?>
<div class="wrap dash"><h1>Admin Dashboard</h1>
<div class="grid" style="padding:24px 0">
  <?php foreach (['customer' => 'Customers', 'owner' => 'Business Owners', 'courier' => 'Couriers'] as $r => $l): ?>
    <div class="card"><h3><?= $l ?></h3><div class="num"><?= $count($r) ?></div></div>
  <?php endforeach; ?>
</div>
<p style="display:flex;gap:10px;flex-wrap:wrap"><a class="btn btn-sm" href="orders.php">View all orders</a> <a class="btn btn-sm" href="accounts.php">Manage accounts</a></p>
<h2>Pending Approvals</h2>
<table><tr><th>Name</th><th>Email</th><th>Role</th><th>Action</th></tr>
<?php foreach ($pending as $p): ?>
  <tr><td><?= e($p['name']) ?></td><td><?= e($p['email']) ?></td><td><?= e($p['role']) ?></td>
  <td><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>">
    <button class="btn btn-sm">Approve</button></form></td></tr>
<?php endforeach; if (!$pending): ?><tr><td colspan="4">No pending requests.</td></tr><?php endif; ?>
</table></div>
<?php require '../includes/footer.php'; ?>
