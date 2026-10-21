<?php
require '../config.php'; require '../includes/phase4.php'; require_role('customer');
$id = (int)($_GET['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $do = $_POST['do'] ?? 'cancel';
    if ($do === 'review') {
        $rr = (int)($_POST['restaurant_rating'] ?? 0); $cr = (int)($_POST['courier_rating'] ?? 0);
        $cm = mb_substr(trim($_POST['comment'] ?? ''), 0, 500);
        $q = db()->prepare("SELECT restaurant_id, courier_id FROM orders WHERE id=? AND customer_id=? AND status='delivered'");
        $q->execute([$id, user()['id']]); $o = $q->fetch();
        if ($o && $rr >= 1 && $rr <= 5) {
            try {
                db()->prepare('INSERT INTO reviews(order_id,customer_id,restaurant_id,courier_id,restaurant_rating,courier_rating,comment) VALUES(?,?,?,?,?,?,?)')
                    ->execute([$id, user()['id'], $o['restaurant_id'], $o['courier_id'], $rr, ($o['courier_id'] && $cr >= 1 && $cr <= 5) ? $cr : null, $cm]);
                flash('Thanks for your review!');
            } catch (PDOException $ex) { flash('You already reviewed this order.', 'error'); }
        } else flash('Please choose a star rating for the restaurant.', 'error');
    } else {
        db()->prepare("UPDATE orders SET status='cancelled' WHERE id=? AND customer_id=? AND status='placed'")->execute([$id, user()['id']]);
        flash('Order cancelled.');
    }
    redirect("customer/order.php?id=$id");
}
$q = db()->prepare('SELECT o.*, r.name rn, c.name cn FROM orders o JOIN restaurants r ON r.id=o.restaurant_id LEFT JOIN users c ON c.id=o.courier_id WHERE o.id=? AND o.customer_id=?');
$q->execute([$id, user()['id']]); $o = $q->fetch(); if (!$o) redirect('customer/orders.php');
$it = db()->prepare('SELECT * FROM order_items WHERE order_id=?'); $it->execute([$id]); $items = $it->fetchAll();
$rv = db()->prepare('SELECT * FROM reviews WHERE order_id=?'); $rv->execute([$id]); $review = $rv->fetch();
$refresh = !in_array($o['status'], ['delivered', 'cancelled']);
$keys = array_keys(ORDER_FLOW); $pos = array_search($o['status'], $keys);
$title = 'Order #' . $id; require '../includes/header.php'; ?>
<div class="wrap dash"><h1>Order #<?= $id ?> · <?= e($o['rn']) ?></h1>
<?php if ($o['status'] === 'cancelled'): ?><p class="alert error">This order was cancelled.</p>
<?php else: ?><div class="steps"><?php foreach ($keys as $n => $k): ?><div class="<?= $n <= $pos ? 'done' : '' ?>"><?= e(ORDER_FLOW[$k]) ?></div><?php endforeach; ?></div><?php endif; ?>
<div class="card"><?php foreach ($items as $i): ?><div class="total"><span><?= $i['qty'] ?>× <?= e($i['name']) ?></span><span><?= money($i['qty'] * $i['price']) ?></span></div><?php endforeach; ?>
<div class="total"><span>Delivery fee</span><span><?= money($o['delivery_fee']) ?></span></div>
<div class="total big"><span>Pay in cash on delivery</span><span><?= money($o['total']) ?></span></div>
<hr style="border:0;border-top:1px solid var(--line);margin:14px 0">
<p><strong>Deliver to:</strong> <?= e($o['address']) ?> · <?= e($o['phone']) ?></p>
<?php if ($o['cn']): ?><p><strong>Your courier:</strong> <?= e($o['cn']) ?></p><?php endif; ?>
<?php if ($o['status'] === 'placed'): ?><form method="post" class="acts"><?= csrf_field() ?><button class="btn btn-out" name="do" value="cancel" onclick="return confirm('Cancel this order?')">Cancel order</button></form><?php endif; ?></div>

<?php if ($o['status'] === 'delivered'): ?>
  <h2 class="sec">Your review</h2>
  <?php if ($review): ?><div class="card review">
    <p><strong><?= e($o['rn']) ?></strong> <span class="stars"><?= star_str((int)$review['restaurant_rating']) ?></span></p>
    <?php if ($review['courier_rating']): ?><p>Courier <?= e($o['cn']) ?> <span class="stars"><?= star_str((int)$review['courier_rating']) ?></span></p><?php endif; ?>
    <?php if ($review['comment'] !== null && $review['comment'] !== ''): ?><p class="muted">“<?= e($review['comment']) ?>”</p><?php endif; ?></div>
  <?php else: ?><form class="card form" method="post"><?= csrf_field() ?><input type="hidden" name="do" value="review">
    <label>How was the food from <?= e($o['rn']) ?>?</label><?= rating_input('restaurant_rating') ?>
    <?php if ($o['cn']): ?><label>How was the delivery by <?= e($o['cn']) ?>?</label><?= rating_input('courier_rating', false) ?><?php endif; ?>
    <label>Comment (optional)</label><textarea name="comment" rows="3" maxlength="500" placeholder="Tell others what you thought"></textarea>
    <button class="btn">Submit review</button></form>
  <?php endif; ?>
<?php endif; ?></div>
<?php require '../includes/footer.php'; ?>
