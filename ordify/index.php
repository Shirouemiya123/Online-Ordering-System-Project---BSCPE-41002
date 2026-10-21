<?php
require_once 'config.php';
$u = user();
$featured = []; $restaurantCount = 0; $openCount = 0;
try {
    $restaurantCount = (int)db()->query("SELECT COUNT(*) FROM restaurants r JOIN users u ON u.id=r.owner_id WHERE u.status='active'")->fetchColumn();
    $openCount = (int)db()->query("SELECT COUNT(*) FROM restaurants r JOIN users u ON u.id=r.owner_id WHERE u.status='active' AND r.is_open=1")->fetchColumn();
    $featured = db()->query("SELECT r.id,r.name,r.description,r.address,r.image,r.is_open,r.cuisine,
        (SELECT ROUND(AVG(v.restaurant_rating),1) FROM reviews v WHERE v.restaurant_id=r.id) avg_rating,
        (SELECT COUNT(*) FROM reviews v WHERE v.restaurant_id=r.id) review_count
        FROM restaurants r JOIN users u ON u.id=r.owner_id WHERE u.status='active'
        ORDER BY r.is_open DESC, avg_rating IS NULL, avg_rating DESC, r.id DESC LIMIT 6")->fetchAll();
} catch (Throwable $e) { $featured = []; }
$title = 'Fresh food, good mood.'; require 'includes/header.php'; ?>
<section class="hero hero-premium"><div class="wrap hero-layout">
  <div class="hero-copy">
    <span class="eyebrow"><span class="eyebrow-dot"></span> LOCAL FLAVORS, RIGHT AROUND YOU</span>
    <h1>Good food.<br><span>Good mood.</span><br>Delivered.</h1>
    <p>Discover neighborhood favorites, support local restaurants, and enjoy your next great meal without the hassle.</p>
    <div class="hero-actions">
      <?php if ($u && $u['role'] === 'customer'): ?><a class="btn" href="customer/index.php">Explore restaurants <span aria-hidden="true">→</span></a>
      <?php elseif ($u): ?><a class="btn" href="<?= APP_URL . '/' . home_for($u['role']) ?>">Go to your dashboard <span aria-hidden="true">→</span></a>
      <?php else: ?><a class="btn" href="register.php">Start exploring <span aria-hidden="true">→</span></a><a class="btn btn-ghost hero-login" href="login.php">Sign in</a><?php endif; ?>
    </div>
    <div class="hero-proof"><div class="proof-avatars" aria-hidden="true"><span>🍜</span><span>🍔</span><span>🍕</span></div><p><strong><?= number_format($restaurantCount) ?> local spots</strong><br><span><?= number_format($openCount) ?> currently open for orders</span></p></div>
  </div>
  <div class="hero-visual" aria-label="Fresh meal illustration" role="img"><div class="hero-glow"></div><div class="food-orbit orbit-one">✦</div><div class="food-orbit orbit-two">✦</div><div class="food-plate"><span class="plate-food">🍲</span></div><div class="floating-note note-top"><span class="note-icon">✓</span><span><strong>Freshly made</strong><small>From local kitchens</small></span></div><div class="floating-note note-bottom"><span class="note-icon">⌁</span><span><strong>Easy ordering</strong><small>Pay when it arrives</small></span></div></div>
</div></section>
<section class="wrap home-discovery" id="discover">
  <div class="section-heading"><div><span class="eyebrow eyebrow-green">MADE FOR YOUR CRAVINGS</span><h2>Find your next favorite.</h2><p>Good things are cooking in your neighborhood.</p></div><?php if ($u && $u['role'] === 'customer'): ?><a class="text-link" href="customer/index.php">View all restaurants <span aria-hidden="true">→</span></a><?php else: ?><a class="text-link" href="register.php">Join ORDIFY <span aria-hidden="true">→</span></a><?php endif; ?></div>
  <div class="quick-categories" aria-label="Popular food categories"><a href="<?= $u && $u['role']==='customer' ? 'customer/index.php?cuisine=Filipino' : 'register.php' ?>"><span>🍛</span> Filipino</a><a href="<?= $u && $u['role']==='customer' ? 'customer/index.php?cuisine=Fast+food' : 'register.php' ?>"><span>🍔</span> Fast food</a><a href="<?= $u && $u['role']==='customer' ? 'customer/index.php?cuisine=Pizza' : 'register.php' ?>"><span>🍕</span> Pizza</a><a href="<?= $u && $u['role']==='customer' ? 'customer/index.php?cuisine=Asian' : 'register.php' ?>"><span>🍜</span> Asian</a><a href="<?= $u && $u['role']==='customer' ? 'customer/index.php?cuisine=Coffee+%26+Dessert' : 'register.php' ?>"><span>🧋</span> Coffee &amp; dessert</a></div>
  <div class="section-heading featured-heading"><div><h2>Neighborhood favorites</h2><p>Handpicked from the restaurants on ORDIFY.</p></div></div>
  <?php if ($featured): ?><div class="rgrid home-restaurants" data-reveal-group><?php foreach ($featured as $r): ?><a class="card rcard home-restaurant-card" href="<?= $u && $u['role']==='customer' ? 'customer/restaurant.php?id=' . (int)$r['id'] : 'login.php' ?>"><div class="home-card-image"><?php if ($r['image']): ?><img class="thumb" loading="lazy" src="<?= APP_URL ?>/uploads/<?= e($r['image']) ?>" alt="<?= e($r['name']) ?>"><?php else: ?><div class="thumb ph home-food-placeholder"><span>🍽️</span></div><?php endif; ?><span class="tag <?= $r['is_open'] ? '' : 'off' ?> home-open-tag"><?= $r['is_open'] ? 'Open now' : 'Closed' ?></span></div><div class="home-card-copy"><div class="home-card-meta"><span><?= e($r['cuisine'] ?? 'Local favorite') ?></span><span class="stars">★ <?= $r['review_count'] ? e(number_format((float)$r['avg_rating'],1)) : 'New' ?></span></div><h3><?= e($r['name']) ?></h3><p class="muted"><?= e($r['address']) ?></p><span class="home-card-cta">View restaurant <span aria-hidden="true">→</span></span></div></a><?php endforeach; ?></div>
  <?php else: ?><div class="card empty-featured"><span class="empty-icon">🍽️</span><h3>Your next favorite could be here.</h3><p>Restaurants will appear here as local owners set up their ORDIFY profiles.</p><?php if (!$u): ?><a class="btn" href="register.php">Create an account</a><?php elseif ($u['role'] === 'customer'): ?><a class="btn" href="customer/index.php">Browse restaurants</a><?php endif; ?></div><?php endif; ?>
</section>
<section class="home-how"><div class="wrap"><div class="section-heading"><div><span class="eyebrow eyebrow-green">SIMPLE BY DESIGN</span><h2>Your next meal, in three steps.</h2></div></div><div class="how-grid" data-reveal-group><article class="how-card"><span class="how-number">01</span><span class="how-emoji">🔎</span><h3>Discover</h3><p>Explore local restaurants and find something that hits the spot.</p></article><article class="how-card"><span class="how-number">02</span><span class="how-emoji">🛍️</span><h3>Order</h3><p>Choose your favorites and check out with cash on delivery.</p></article><article class="how-card"><span class="how-number">03</span><span class="how-emoji">🛵</span><h3>Enjoy</h3><p>Follow your order's progress and get ready to dig in.</p></article></div></div></section>
<section class="wrap home-join"><div class="join-panel"><div><span class="eyebrow">A PLACE FOR LOCAL BUSINESS</span><h2>Have a kitchen or love delivering?</h2><p>Bring your food business or courier service to the ORDIFY community.</p></div><a class="btn join-btn" href="register.php">Join ORDIFY <span aria-hidden="true">→</span></a></div></section>
<?php require 'includes/footer.php'; ?>
