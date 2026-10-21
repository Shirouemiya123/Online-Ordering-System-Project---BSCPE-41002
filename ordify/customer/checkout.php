<?php
require '../config.php'; require_role('customer');
if (empty($_SESSION['cart']['items'])) redirect('customer/cart.php');
$rid = (int)$_SESSION['cart']['rid']; $cart = $_SESSION['cart']['items'];
$ids = array_keys($cart); $in = implode(',', array_fill(0, count($ids), '?'));
$q = db()->prepare("SELECT * FROM menu_items WHERE id IN ($in) AND restaurant_id=? AND is_available=1"); $q->execute([...$ids, $rid]);
$items = $q->fetchAll();
$q = db()->prepare('SELECT * FROM restaurants WHERE id=? AND is_open=1'); $q->execute([$rid]); $rest = $q->fetch();
if (!$rest || count($items) !== count($ids)) { flash('Some items are no longer available. Please review your cart.', 'error'); redirect('customer/cart.php'); }
$sub = 0; foreach ($items as $i) $sub += $i['price'] * $cart[$i['id']];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $addr = trim($_POST['address']); $phone = trim($_POST['phone']);
    if ($addr === '' || !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) flash('Enter a delivery address and a valid phone number.', 'error');
    else {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO orders(customer_id,restaurant_id,address,phone,notes,subtotal,delivery_fee,total) VALUES(?,?,?,?,?,?,?,?)')
                ->execute([user()['id'], $rid, $addr, $phone, trim($_POST['notes'] ?? ''), $sub, DELIVERY_FEE, $sub + DELIVERY_FEE]);
            $oid = $pdo->lastInsertId();
            $ins = $pdo->prepare('INSERT INTO order_items(order_id,name,price,qty) VALUES(?,?,?,?)');
            foreach ($items as $i) $ins->execute([$oid, $i['name'], $i['price'], $cart[$i['id']]]);
            $pdo->commit();
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('Could not place your order. Please try again.', 'error'); redirect('customer/checkout.php');
        }
        // Send the receipt only after the order transaction commits. Email failure
        // must not cancel or duplicate the customer's order.
        $customerQ = $pdo->prepare('SELECT name, email FROM users WHERE id=?');
        $customerQ->execute([user()['id']]);
        $customer = $customerQ->fetch() ?: ['name' => user()['name'] ?? 'ORDIFY Customer', 'email' => ''];

        $orderQ = $pdo->prepare('SELECT created_at, payment_method, status FROM orders WHERE id=? AND customer_id=?');
        $orderQ->execute([$oid, user()['id']]);
        $savedOrder = $orderQ->fetch() ?: [];
        $receiptOrder = [
            'id' => (int)$oid,
            'created_at' => $savedOrder['created_at'] ?? date('Y-m-d H:i:s'),
            'restaurant_name' => $rest['name'],
            'address' => $addr,
            'subtotal' => $sub,
            'delivery_fee' => DELIVERY_FEE,
            'total' => $sub + DELIVERY_FEE,
            'payment_method' => (($savedOrder['payment_method'] ?? 'COD') === 'COD') ? 'Cash on Delivery' : (string)$savedOrder['payment_method'],
            'status' => $savedOrder['status'] ?? 'placed',
        ];
        $receiptItems = [];
        foreach ($items as $item) {
            $receiptItems[] = [
                'name' => $item['name'],
                'price' => (float)$item['price'],
                'qty' => (int)$cart[$item['id']],
            ];
        }
        $receiptSent = send_order_receipt($customer, $receiptOrder, $receiptItems);

        $_SESSION['cart'] = null;
        if ($receiptSent) {
            flash('Order placed! Your receipt email has been sent. Please prepare the exact cash amount.');
        } else {
            flash('Order placed successfully, but the receipt email could not be sent. Your order is saved; check storage/mail.log for the email error.', 'info');
        }
        redirect("customer/order.php?id=$oid");
    }
}
$title = 'Checkout'; require '../includes/header.php'; ?>
<div class="wrap"><div class="cols" style="margin:40px 0">
<form class="card form" method="post"><h2 style="color:var(--green-d)">Delivery details</h2><?= csrf_field() ?>
  <label>Delivery address</label><input name="address" required value="<?= e($_POST['address'] ?? '') ?>" placeholder="House no., street, barangay, city">
  <label>Contact number</label><input name="phone" required value="<?= e($_POST['phone'] ?? '') ?>">
  <label>Notes for rider / restaurant (optional)</label><input name="notes" maxlength="255">
  <p class="alert">Payment method: <strong>Cash on Delivery</strong>. Pay the courier when your food arrives.</p>
  <button class="btn">Place order · <?= money($sub + DELIVERY_FEE) ?></button></form>
<div class="card"><h3>Order summary · <?= e($rest['name']) ?></h3>
<?php foreach ($items as $i): ?><div class="total"><span><?= $cart[$i['id']] ?>× <?= e($i['name']) ?></span><span><?= money($i['price'] * $cart[$i['id']]) ?></span></div><?php endforeach; ?>
<div class="total"><span>Delivery fee</span><span><?= money(DELIVERY_FEE) ?></span></div>
<div class="total big"><span>Total</span><span><?= money($sub + DELIVERY_FEE) ?></span></div></div>
</div></div>
<?php require '../includes/footer.php'; ?>
