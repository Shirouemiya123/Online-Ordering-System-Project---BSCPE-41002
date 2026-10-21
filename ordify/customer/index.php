<?php
require '../config.php'; require '../includes/phase4.php'; require_role('customer');
$s = trim($_GET['q'] ?? ''); $cu = $_GET['cuisine'] ?? ''; $sort = $_GET['sort'] ?? 'rating';
$open = !empty($_GET['open']); $min = (float)($_GET['min'] ?? 0);

$sql = 'SELECT * FROM (SELECT r.*, ' . RATING_SQL . " FROM restaurants r JOIN users u ON u.id=r.owner_id WHERE u.status='active') t WHERE 1=1";
$args = [];
if ($s !== '') {
    $sql .= ' AND (t.name LIKE ? OR t.description LIKE ? OR t.cuisine LIKE ? OR t.id IN (SELECT restaurant_id FROM menu_items WHERE name LIKE ?))';
    array_push($args, ...array_fill(0, 4, "%$s%"));
}
if (in_array($cu, CUISINES, true)) { $sql .= ' AND t.cuisine=?'; $args[] = $cu; } else $cu = '';
if ($open) $sql .= ' AND t.is_open=1';
if ($min > 0) { $sql .= ' AND t.avg_rating >= ?'; $args[] = $min; }
$orders = ['rating' => 't.avg_rating IS NULL, t.avg_rating DESC, t.review_count DESC, t.name', 'name' => 't.name', 'new' => 't.id DESC'];
$sql .= ' ORDER BY t.is_open DESC, ' . ($orders[$sort] ?? $orders['rating']);
$q = db()->prepare($sql); $q->execute($args); $list = $q->fetchAll();
$filtered = $s !== '' || $cu !== '' || $open || $min > 0;

$title = 'Browse Restaurants'; require '../includes/header.php'; ?>
<div class="wrap dash"><h1>Hungry, <?= e(user()['name']) ?>?</h1>
<form class="bar filters" method="get">
  <input name="q" placeholder="Search restaurants, cuisines or dishes..." value="<?= e($s) ?>" data-filter-target="#restaurant-results" data-filter-count="#restaurant-filter-count" aria-label="Filter visible restaurants">
  <select name="cuisine"><option value="">All cuisines</option>
    <?php foreach (CUISINES as $c): ?><option <?= $cu === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select>
  <select name="min"><option value="0">Any rating</option>
    <?php foreach ([3, 4, 4.5] as $m): ?><option value="<?= $m ?>" <?= $min == $m ? 'selected' : '' ?>><?= $m ?>★ &amp; up</option><?php endforeach; ?></select>
  <select name="sort">
    <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Top rated</option>
    <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>A to Z</option>
    <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Newest</option></select>
  <label class="chk"><input type="checkbox" name="open" value="1" <?= $open ? 'checked' : '' ?>> Open now</label>
  <button class="btn">Search</button>
  <?php if ($filtered): ?><a class="link" href="index.php">Clear</a><?php endif; ?>
</form>
<p id="restaurant-filter-count" class="filter-count" aria-live="polite"><?= count($list) ?> <?= count($list) === 1 ? 'result' : 'results' ?></p><p class="muted" style="text-align:left;margin-bottom:12px"><?= count($list) ?> restaurant<?= count($list) === 1 ? '' : 's' ?> found</p>
<div class="rgrid" id="restaurant-results">
<?php foreach ($list as $r): ?>
  <a class="card rcard" data-filterable href="restaurant.php?id=<?= $r['id'] ?>"><?= thumb($r['image']) ?>
    <h3><?= e($r['name']) ?></h3>
    <p class="muted"><?= e($r['cuisine']) ?> · <?= e($r['address']) ?></p>
    <?= rating_badge($r['avg_rating'], $r['review_count']) ?>
    <span class="tag <?= $r['is_open'] ? '' : 'off' ?>"><?= $r['is_open'] ? 'Open' : 'Closed' ?></span></a>
<?php endforeach; if (!$list) echo '<p class="muted">No restaurants match your search.' . ($filtered ? ' <a href="index.php">Clear filters</a>' : '') . '</p>'; ?>
</div></div>
<?php require '../includes/footer.php'; ?>
