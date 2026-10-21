<?php
require '../config.php'; require_role('courier'); $uid = user()['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $id = (int)$_POST['id']; $do = $_POST['do'];
    if ($do === 'claim') db()->prepare("UPDATE orders SET courier_id=? WHERE id=? AND status='ready' AND courier_id IS NULL")->execute([$uid, $id]);
    elseif ($do === 'pickup') db()->prepare("UPDATE orders SET status='picked_up' WHERE id=? AND courier_id=? AND status='ready'")->execute([$id, $uid]);
    elseif ($do === 'deliver') db()->prepare("UPDATE orders SET status='delivered' WHERE id=? AND courier_id=? AND status='picked_up'")->execute([$id, $uid]);
    redirect('courier/index.php');
}
$base = 'SELECT o.*, r.name rn, r.address ra, c.name cn, ' . LINES_SQL . ' FROM orders o JOIN restaurants r ON r.id=o.restaurant_id JOIN users c ON c.id=o.customer_id ';
$avail = db()->query($base . "WHERE o.status='ready' AND o.courier_id IS NULL ORDER BY o.id")->fetchAll();
$q = db()->prepare($base . "WHERE o.courier_id=? AND o.status IN ('ready','picked_up') ORDER BY o.id"); $q->execute([$uid]); $mine = $q->fetchAll();
$q = db()->prepare("SELECT COUNT(*), COALESCE(SUM(delivery_fee),0) FROM orders WHERE courier_id=? AND status='delivered'"); $q->execute([$uid]); [$done, $earn] = $q->fetch(PDO::FETCH_NUM);
$q = db()->prepare('SELECT ROUND(AVG(courier_rating),1), COUNT(courier_rating) FROM reviews WHERE courier_id=?'); $q->execute([$uid]); [$avg, $cnt] = $q->fetch(PDO::FETCH_NUM);
$refresh = true; $title = 'Courier'; require '../includes/header.php'; ?>
<div class="wrap dash"><h1>Hello, <?= e(user()['name']) ?></h1>
<div class="grid" style="padding:24px 0"><div class="card"><h3>Completed</h3><div class="num"><?= $done ?></div></div><div class="card"><h3>Delivery earnings</h3><div class="num"><?= money($earn) ?></div></div><div class="card"><h3>Your rating</h3><div class="num"><?= $cnt ? '★ ' . number_format((float)$avg, 1) : '–' ?></div><p class="muted"><?= (int)$cnt ?> rating<?= $cnt == 1 ? '' : 's' ?></p></div></div>
<h2 class="sec">My active deliveries</h2><div class="filters dashboard-filters"><input type="search" placeholder="Search my active deliveries..." data-filter-target="#courier-active-list" data-filter-count="#courier-active-count" aria-label="Search active deliveries"><span id="courier-active-count" class="filter-count" aria-live="polite"><?= count($mine) ?> <?= count($mine) === 1 ? 'result' : 'results' ?></span></div><div id="courier-active-list">
<?php foreach ($mine as $o): ?><div class="card ocard" data-filterable data-status="<?= e($o['status']) ?>"><div class="head"><strong>#<?= $o['id'] ?> · <?= e($o['rn']) ?></strong><?= status_tag($o['status']) ?></div>
<p><?= e($o['lines']) ?></p><p><strong>Pickup:</strong> <?= e($o['ra']) ?></p><p><strong>Deliver to:</strong> <?= e($o['cn']) ?>, <?= e($o['address']) ?> · <?= e($o['phone']) ?></p>
<p><?= e($o['notes']) ?></p><p class="alert">Collect <strong><?= money($o['total']) ?></strong> in cash.</p>
<form method="post" class="acts"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $o['id'] ?>">
<?php if ($o['status'] === 'ready'): ?><button class="btn btn-sm" name="do" value="pickup">Picked up from restaurant</button>
<?php else: ?><button class="btn btn-sm" name="do" value="deliver" data-confirm="Confirm that this delivery is complete and cash has been collected?">Delivered &amp; cash collected</button><?php endif; ?></form></div>
<?php endforeach; if (!$mine) echo '<p class="muted">No active deliveries.</p>'; ?></div>
<h2 class="sec">Available orders</h2><div class="filters dashboard-filters"><input type="search" placeholder="Search available deliveries..." data-filter-target="#courier-available-list" data-filter-count="#courier-available-count" aria-label="Search available deliveries"><span id="courier-available-count" class="filter-count" aria-live="polite"><?= count($avail) ?> <?= count($avail) === 1 ? 'result' : 'results' ?></span></div><div id="courier-available-list">
<?php foreach ($avail as $o): ?><div class="card ocard" data-filterable><div class="head"><strong>#<?= $o['id'] ?> · <?= e($o['rn']) ?></strong><span class="price">Fee <?= money($o['delivery_fee']) ?></span></div>
<p class="muted">Pickup: <?= e($o['ra']) ?></p><form method="post" class="acts"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $o['id'] ?>"><button class="btn btn-sm" name="do" value="claim">Accept delivery</button></form></div>
<?php endforeach; if (!$avail) echo '<p class="muted">No orders waiting for a courier.</p>'; ?></div></div>
<?php require '../includes/footer.php'; ?>
