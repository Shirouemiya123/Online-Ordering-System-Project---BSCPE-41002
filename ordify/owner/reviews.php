<?php
require '../config.php'; require '../includes/phase4.php'; require_role('owner');
$r = my_restaurant(); if (!$r) redirect('owner/restaurant.php');
$q = db()->prepare('SELECT ROUND(AVG(restaurant_rating),1), COUNT(*) FROM reviews WHERE restaurant_id=?'); $q->execute([$r['id']]); [$avg, $cnt] = $q->fetch(PDO::FETCH_NUM);
$q = db()->prepare('SELECT restaurant_rating, COUNT(*) n FROM reviews WHERE restaurant_id=? GROUP BY restaurant_rating'); $q->execute([$r['id']]);
$dist = array_fill(1, 5, 0); foreach ($q->fetchAll() as $d) $dist[(int)$d['restaurant_rating']] = (int)$d['n'];
$q = db()->prepare('SELECT v.*, u.name FROM reviews v JOIN users u ON u.id=v.customer_id WHERE v.restaurant_id=? ORDER BY v.id DESC LIMIT 50'); $q->execute([$r['id']]); $list = $q->fetchAll();
$title = 'Reviews'; require '../includes/header.php'; ?>
<div class="wrap dash"><h1>Customer Reviews</h1>
<div class="card" style="margin:20px 0"><div class="num"><?= $cnt ? '★ ' . number_format((float)$avg, 1) : '–' ?></div><p class="muted"><?= (int)$cnt ?> review<?= $cnt == 1 ? '' : 's' ?></p>
<?php if ($cnt) for ($i = 5; $i >= 1; $i--): ?><div class="bar-row"><span><?= $i ?>★</span><div class="bar-bg"><div style="width:<?= round($dist[$i] / $cnt * 100) ?>%"></div></div><span><?= $dist[$i] ?></span></div><?php endfor; ?></div>
<?php foreach ($list as $v): ?><div class="card review"><div class="head"><strong><?= e(explode(' ', $v['name'])[0]) ?> · order #<?= $v['order_id'] ?></strong><span class="stars"><?= star_str((int)$v['restaurant_rating']) ?></span></div>
<?php if ($v['comment'] !== null && $v['comment'] !== ''): ?><p><?= e($v['comment']) ?></p><?php endif; ?><p class="muted"><?= e(substr($v['created_at'], 0, 10)) ?></p></div>
<?php endforeach; if (!$list) echo '<p class="muted">No reviews yet. They appear here after customers rate delivered orders.</p>'; ?></div>
<?php require '../includes/footer.php'; ?>
