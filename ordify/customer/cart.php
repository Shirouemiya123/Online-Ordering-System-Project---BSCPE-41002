<?php
require '../config.php'; require_role('customer');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $a = $_POST['action'] ?? ''; $id = (int)($_POST['item_id'] ?? 0);
    if ($a === 'add') {
        $q = db()->prepare('SELECT i.id,i.restaurant_id FROM menu_items i JOIN restaurants r ON r.id=i.restaurant_id WHERE i.id=? AND i.is_available=1 AND r.is_open=1'); $q->execute([$id]);
        if ($it = $q->fetch()) {
            if (($_SESSION['cart']['rid'] ?? $it['restaurant_id']) != $it['restaurant_id']) { $_SESSION['cart'] = null; flash('Your cart was reset: one order can only include one restaurant.'); }
            $_SESSION['cart']['rid'] = $it['restaurant_id'];
            $_SESSION['cart']['items'][$id] = min(20, ($_SESSION['cart']['items'][$id] ?? 0) + 1);
        }
    } elseif ($a === 'set') { $n = max(0, min(20, (int)$_POST['qty'])); if ($n) $_SESSION['cart']['items'][$id] = $n; else unset($_SESSION['cart']['items'][$id]); }
    elseif ($a === 'remove') unset($_SESSION['cart']['items'][$id]);
    elseif ($a === 'clear') $_SESSION['cart'] = null;
    if (empty($_SESSION['cart']['items'])) $_SESSION['cart'] = null;
    redirect('customer/cart.php');
}
$rows = []; $sub = 0; $rest = null;
if (!empty($_SESSION['cart']['items'])) {
    $ids = array_keys($_SESSION['cart']['items']);
    $q = db()->prepare('SELECT * FROM menu_items WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')'); $q->execute($ids);
    foreach ($q->fetchAll() as $i) { $i['qty'] = $_SESSION['cart']['items'][$i['id']]; $sub += $i['qty'] * $i['price']; $rows[] = $i; }
    $q = db()->prepare('SELECT name FROM restaurants WHERE id=?'); $q->execute([$_SESSION['cart']['rid']]); $rest = $q->fetchColumn();
}
$title = 'Your Cart'; require '../includes/header.php'; ?>
<div class="wrap dash"><h1>Your Cart</h1>
<?php if (!$rows): ?><p class="muted" style="margin:20px 0">Your cart is empty. <a href="index.php">Browse restaurants</a></p>
<?php else: ?><div class="card" style="margin-top:20px"><h3>From <?= e($rest) ?></h3>
  <?php foreach ($rows as $i): ?><div class="row"><?= thumb($i['image'], 'thumb sm') ?>
    <div><strong><?= e($i['name']) ?></strong><br><span class="price"><?= money($i['price']) ?></span></div>
    <div><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="set"><input type="hidden" name="item_id" value="<?= $i['id'] ?>">
      <input class="qty" type="number" name="qty" min="0" max="20" value="<?= $i['qty'] ?>"> <button class="link">Update</button></form>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="item_id" value="<?= $i['id'] ?>"><button class="link">Remove</button></form></div>
    <div><strong><?= money($i['qty'] * $i['price']) ?></strong></div></div>
  <?php endforeach; ?>
  <div style="margin-top:16px"><div class="total"><span>Subtotal</span><span><?= money($sub) ?></span></div>
  <div class="total"><span>Delivery fee</span><span><?= money(DELIVERY_FEE) ?></span></div>
  <div class="total big"><span>Total (Cash on Delivery)</span><span><?= money($sub + DELIVERY_FEE) ?></span></div></div>
  <a class="btn" href="checkout.php" style="margin-top:14px">Proceed to checkout</a>
  <form method="post"><?= csrf_field() ?><button class="link" name="action" value="clear">Clear cart</button></form>
</div><?php endif; ?></div>
<?php require '../includes/footer.php'; ?>
