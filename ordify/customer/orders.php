<?php
require '../config.php'; require_role('customer');
$q = db()->prepare('SELECT o.*, r.name rn, v.id AS rv, ' . LINES_SQL . ' FROM orders o JOIN restaurants r ON r.id=o.restaurant_id LEFT JOIN reviews v ON v.order_id=o.id WHERE o.customer_id=? ORDER BY o.id DESC'); $q->execute([user()['id']]);
$list = $q->fetchAll(); $title = 'My Orders'; require '../includes/header.php'; ?>
<div class="wrap dash"><h1>My Orders</h1>
<div class="filters dashboard-filters" style="margin-top:20px"><input type="search" placeholder="Search order number, restaurant, or items..." data-filter-target="#customer-order-list" data-filter-count="#customer-order-count" aria-label="Search orders"><span id="customer-order-count" class="filter-count" aria-live="polite"><?= count($list) ?> <?= count($list) === 1 ? 'result' : 'results' ?></span></div><div id="customer-order-list" style="margin-top:14px">
<?php foreach ($list as $o): ?><a class="card ocard rcard" data-filterable href="order.php?id=<?= $o['id'] ?>"><div class="head"><strong>#<?= $o['id'] ?> · <?= e($o['rn']) ?></strong><span><?= $o['status'] === 'delivered' && !$o['rv'] ? '<span class="tag rate-cta">★ Rate this order</span> ' : '' ?><?= status_tag($o['status']) ?></span></div>
<p class="muted"><?= e($o['lines']) ?></p><strong><?= money($o['total']) ?></strong> <span class="muted">· <?= e($o['created_at']) ?></span></a>
<?php endforeach; if (!$list) echo '<p class="muted">No orders yet. <a href="index.php">Order something tasty</a></p>'; ?></div></div>
<?php require '../includes/footer.php'; ?>
