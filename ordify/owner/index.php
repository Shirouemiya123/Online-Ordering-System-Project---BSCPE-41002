<?php
require '../config.php'; require '../includes/phase4.php'; require_role('owner');
$r = my_restaurant();
$items = $r ? db()->query('SELECT COUNT(*) FROM menu_items WHERE restaurant_id=' . (int)$r['id'])->fetchColumn() : 0;
$new = 0; $avg = null; $cnt = 0; $todayOrders = 0; $todaySales = 0.0; $activeOrders = 0;
if ($r) {
    $q = db()->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id=? AND status='placed'"); $q->execute([$r['id']]); $new = (int)$q->fetchColumn();
    $q = db()->prepare('SELECT ROUND(AVG(restaurant_rating),1), COUNT(*) FROM reviews WHERE restaurant_id=?'); $q->execute([$r['id']]); [$avg, $cnt] = $q->fetch(PDO::FETCH_NUM);
    $q = db()->prepare("SELECT COUNT(*), COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total ELSE 0 END),0) FROM orders WHERE restaurant_id=? AND DATE(created_at)=CURDATE()"); $q->execute([$r['id']]); [$todayOrders, $todaySales] = $q->fetch(PDO::FETCH_NUM);
    $q = db()->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id=? AND status IN ('placed','accepted','preparing','ready')"); $q->execute([$r['id']]); $activeOrders = (int)$q->fetchColumn();
}
$title = 'Business Owner'; require '../includes/header.php'; ?>
<div class="wrap dash"><h1>Hello, <?= e(user()['name']) ?></h1>
<div class="grid owner-metrics" style="padding:24px 0"><div class="card metric-card"><span class="metric-kicker">TODAY</span><h3>Orders received</h3><div class="num"><?= (int)$todayOrders ?></div><p class="muted">Orders placed today</p></div><div class="card metric-card"><span class="metric-kicker">TODAY</span><h3>Order value</h3><div class="num"><?= money($todaySales) ?></div><p class="muted">Excludes cancelled orders</p></div><div class="card metric-card"><span class="metric-kicker">NEEDS ATTENTION</span><h3>Active orders</h3><div class="num"><?= (int)$activeOrders ?></div><p class="muted">Not yet delivered or cancelled</p></div></div><div class="grid" style="padding:12px 0 24px">
  <a class="card" href="restaurant.php"><h3>My Restaurant</h3><p><?= $r ? e($r['name']) . ' · ' . ($r['is_open'] ? 'Open' : 'Closed') : 'Set up your restaurant profile' ?></p></a>
  <a class="card" href="menu.php"><h3>Menu</h3><div class="num"><?= $items ?></div><p>items listed</p></a>
  <a class="card" href="orders.php"><h3>Orders</h3><p><?= $new ? "<strong>$new new</strong> waiting for you" : 'View and update incoming orders' ?></p></a>
  <a class="card" href="reviews.php"><h3>Reviews</h3><p><?= rating_badge($avg, $cnt) ?></p><p><?= $cnt ? 'See what customers say' : 'No reviews yet' ?></p></a>
</div></div>
<?php require '../includes/footer.php'; ?>
