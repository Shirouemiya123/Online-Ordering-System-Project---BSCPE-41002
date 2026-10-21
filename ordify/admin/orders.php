<?php
require '../config.php'; require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    db()->prepare("UPDATE orders SET status='cancelled' WHERE id=? AND status NOT IN ('delivered','cancelled')")->execute([(int)$_POST['id']]);
    redirect('admin/orders.php');
}
$list = db()->query('SELECT o.*, r.name rn, c.name cn, k.name kn FROM orders o JOIN restaurants r ON r.id=o.restaurant_id JOIN users c ON c.id=o.customer_id LEFT JOIN users k ON k.id=o.courier_id ORDER BY o.id DESC LIMIT 100')->fetchAll();
$rev = db()->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='delivered'")->fetchColumn();
$title = 'All Orders'; require '../includes/header.php'; ?>
<div class="wrap dash"><h1>All Orders</h1><p class="muted" style="text-align:left">Delivered order value: <strong><?= money($rev) ?></strong></p>
<table><tr><th>#</th><th>Restaurant</th><th>Customer</th><th>Courier</th><th>Total</th><th>Status</th><th></th></tr>
<?php foreach ($list as $o): ?><tr><td><?= $o['id'] ?></td><td><?= e($o['rn']) ?></td><td><?= e($o['cn']) ?></td><td><?= e($o['kn'] ?? '-') ?></td><td><?= money($o['total']) ?></td><td><?= status_tag($o['status']) ?></td>
<td><?php if (!in_array($o['status'], ['delivered', 'cancelled'])): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $o['id'] ?>"><button class="link">Cancel</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></table></div>
<?php require '../includes/footer.php'; ?>
