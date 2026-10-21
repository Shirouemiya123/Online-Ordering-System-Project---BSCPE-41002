<?php
require '../config.php'; require '../includes/phase4.php'; require_role('customer');
$q = db()->prepare("SELECT r.*, " . RATING_SQL . " FROM restaurants r JOIN users u ON u.id=r.owner_id WHERE r.id=? AND u.status='active'"); $q->execute([(int)($_GET['id'] ?? 0)]);
$r = $q->fetch(); if (!$r) redirect('customer/index.php');
$q = db()->prepare('SELECT * FROM menu_items WHERE restaurant_id=? ORDER BY category, name'); $q->execute([$r['id']]);
$menu = []; foreach ($q->fetchAll() as $i) $menu[$i['category']][] = $i;
$q = db()->prepare('SELECT v.*, u.name FROM reviews v JOIN users u ON u.id=v.customer_id WHERE v.restaurant_id=? ORDER BY v.id DESC LIMIT 10'); $q->execute([$r['id']]);
$reviews = $q->fetchAll();
$title = $r['name']; require '../includes/header.php'; ?>
<div class="wrap dash">
  <div class="card"><div class="cols" style="grid-template-columns:260px 1fr"><?= thumb($r['image']) ?>
    <div><h1><?= e($r['name']) ?> <span class="tag <?= $r['is_open'] ? '' : 'off' ?>"><?= $r['is_open'] ? 'Open' : 'Closed' ?></span></h1>
    <p><?= rating_badge($r['avg_rating'], $r['review_count']) ?> <span class="tag"><?= e($r['cuisine']) ?></span></p>
    <p><?= e($r['description']) ?></p><p class="muted"><?= e($r['address']) ?> · <?= e($r['phone']) ?></p></div></div></div>
  <?php foreach ($menu as $cat => $items): ?><h2 class="sec"><?= e($cat) ?></h2><div class="card">
    <?php foreach ($items as $i): ?><div class="row"><?= thumb($i['image'], 'thumb sm') ?>
      <div><strong><?= e($i['name']) ?></strong><br><span class="muted"><?= e($i['description']) ?></span></div>
      <div><span class="price"><?= money($i['price']) ?></span><br>
      <?php if ($r['is_open'] && $i['is_available']): ?>
        <form method="post" action="cart.php"><?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="item_id" value="<?= $i['id'] ?>"><button class="btn btn-sm">Add</button></form>
      <?php else: ?><span class="tag off">Unavailable</span><?php endif; ?></div></div>
    <?php endforeach; ?></div>
  <?php endforeach; if (!$menu) echo '<p class="muted">This restaurant has no menu yet.</p>'; ?>
  <h2 class="sec">Reviews</h2>
  <?php foreach ($reviews as $v): ?><div class="card review"><div class="head"><strong><?= e(explode(' ', $v['name'])[0]) ?></strong><span class="stars"><?= star_str((int)$v['restaurant_rating']) ?></span></div>
    <?php if ($v['comment'] !== null && $v['comment'] !== ''): ?><p><?= e($v['comment']) ?></p><?php endif; ?>
    <p class="muted"><?= e(substr($v['created_at'], 0, 10)) ?></p></div>
  <?php endforeach; if (!$reviews) echo '<p class="muted">No reviews yet. Be the first after your order arrives!</p>'; ?>
</div>
<?php require '../includes/footer.php'; ?>
