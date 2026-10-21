<?php require_once __DIR__ . '/../config.php'; $u = user(); $f = flash(); $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''); $isAdminPage = (($u['role'] ?? '') === 'admin') || str_contains($scriptPath, '/admin/'); ?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<?php if (!empty($refresh)): ?><meta http-equiv="refresh" content="20"><?php endif; ?>
<title><?= e($title ?? 'ORDIFY') ?> | ORDIFY</title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head><?php if ($isAdminPage): ?><body><?php else: ?><body class="enhanced-app" data-theme="light"><?php endif; ?>
<header class="nav"><div class="wrap nav-in">
  <a class="brand" href="<?= APP_URL ?>/index.php">ORD<span>IFY</span></a>
  <nav<?= $isAdminPage ? '' : ' id="primary-nav"' ?>>
    <?php if ($u): ?>
      <a href="<?= APP_URL . '/' . home_for($u['role']) ?>">Dashboard</a>
      <?php $nl = ['customer' => 'My Orders', 'owner' => 'Orders', 'admin' => 'Orders']; if (isset($nl[$u['role']])): ?><a href="<?= APP_URL . '/' . $u['role'] ?>/orders.php"><?= $nl[$u['role']] ?></a><?php endif; ?>
      <?php if ($u['role'] === 'customer'): ?><a href="<?= APP_URL ?>/customer/cart.php">Cart (<?= cart_count() ?>)</a><?php endif; ?>
      <a href="<?= APP_URL ?>/profile.php">Profile</a>
      <span class="chip"><?= e(ucfirst($u['role'])) ?></span>
      <a class="btn btn-sm" href="<?= APP_URL ?>/logout.php">Logout</a>
    <?php else: ?>
      <a href="<?= APP_URL ?>/login.php">Login</a>
      <a class="btn btn-sm" href="<?= APP_URL ?>/register.php">Get Started</a>
    <?php endif; ?>
  </nav>
  <?php if (!$isAdminPage): ?><div class="nav-tools"><button class="icon-btn nav-toggle" type="button" aria-label="Open navigation" aria-controls="primary-nav" aria-expanded="false" data-nav-toggle><span aria-hidden="true">☰</span></button><button class="icon-btn theme-toggle" type="button" aria-label="Switch to dark mode" title="Switch theme" data-theme-toggle><span aria-hidden="true" data-theme-icon>◐</span><span class="theme-label">Dark mode</span></button></div><?php endif; ?>
</div></header>
<main>
<?php if ($f): ?><div class="wrap"><div class="alert <?= e($f[1]) ?>"><?= e($f[0]) ?></div></div><?php endif; ?>
