<?php
require '../config.php'; require_role('owner');
$r = my_restaurant(); if (!$r) redirect('owner/restaurant.php');
$next = ['placed' => 'accepted', 'accepted' => 'preparing', 'preparing' => 'ready'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $id = (int)$_POST['id']; $cur = $_POST['from'] ?? '';
    if ($_POST['do'] === 'reject' && $cur === 'placed') $to = 'cancelled'; elseif (isset($next[$cur])) $to = $next[$cur]; else $to = null;
    if ($to) db()->prepare('UPDATE orders SET status=? WHERE id=? AND restaurant_id=? AND status=?')->execute([$to, $id, $r['id'], $cur]);
    redirect('owner/orders.php');
}
$q = db()->prepare('SELECT o.*, u.name cn, ' . LINES_SQL . ' FROM orders o JOIN users u ON u.id=o.customer_id WHERE o.restaurant_id=? ORDER BY (o.status IN ("delivered","cancelled")), o.id DESC LIMIT 60');
$q->execute([$r['id']]); $list = $q->fetchAll(); $refresh = true;
$labels = ['placed' => 'Accept', 'accepted' => 'Start preparing', 'preparing' => 'Mark ready'];
$title = 'Orders'; require '../includes/header.php'; ?>
<div class="wrap dash"><h1>Incoming Orders</h1><div class="filters dashboard-filters" style="margin-top:20px"><input type="search" placeholder="Search order, customer, or item..." data-filter-target="#owner-order-list" data-filter-count="#owner-order-count" aria-label="Search incoming orders"><select data-status-filter="#owner-order-list" data-filter-count="#owner-order-count" aria-label="Filter order status"><option value="">All statuses</option><option value="placed">New</option><option value="accepted">Accepted</option><option value="preparing">Preparing</option><option value="ready">Ready</option><option value="delivered">Delivered</option><option value="cancelled">Cancelled</option></select><span id="owner-order-count" class="filter-count" aria-live="polite"><?= count($list) ?> <?= count($list) === 1 ? 'result' : 'results' ?></span></div><div id="owner-order-list" style="margin-top:14px">
<?php foreach ($list as $o): ?><div class="card ocard" data-filterable data-status="<?= e($o['status']) ?>"><div class="head"><strong>#<?= $o['id'] ?> · <?= e($o['cn']) ?></strong><?= status_tag($o['status']) ?></div>
<p><?= e($o['lines']) ?></p><p class="muted"><?= e($o['notes']) ?></p><strong><?= money($o['total']) ?> COD</strong>
<?php if (isset($next[$o['status']])): ?><form method="post" class="acts"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $o['id'] ?>"><input type="hidden" name="from" value="<?= $o['status'] ?>">
<button class="btn btn-sm" name="do" value="next"><?= $labels[$o['status']] ?></button>
<?php if ($o['status'] === 'placed'): ?><button class="btn btn-sm btn-out" name="do" value="reject" data-confirm="Reject this order? The customer will see it as cancelled.">Reject</button><?php endif; ?></form><?php endif; ?></div>
<?php endforeach; if (!$list) echo '<p class="muted">No orders yet.</p>'; ?></div></div>
<?php require '../includes/footer.php'; ?>
