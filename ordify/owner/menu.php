<?php
require '../config.php'; require_role('owner');
$r = my_restaurant();
if (!$r) { flash('Set up your restaurant first.', 'error'); redirect('owner/restaurant.php'); }
$rid = (int)$r['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $id = (int)($_POST['id'] ?? 0); $a = $_POST['action'] ?? 'save';
    if ($a === 'delete') db()->prepare('DELETE FROM menu_items WHERE id=? AND restaurant_id=?')->execute([$id, $rid]);
    elseif ($a === 'toggle') db()->prepare('UPDATE menu_items SET is_available=1-is_available WHERE id=? AND restaurant_id=?')->execute([$id, $rid]);
    else {
        $price = (float)$_POST['price'];
        if (trim($_POST['name']) === '' || $price <= 0) { flash('Name and a valid price are required.', 'error'); redirect('owner/menu.php'); }
        $cat = trim($_POST['category']) ?: 'Mains'; $img = save_image($_FILES['image'] ?? []);
        if ($id) {
            db()->prepare('UPDATE menu_items SET name=?,description=?,category=?,price=?,image=COALESCE(?,image) WHERE id=? AND restaurant_id=?')->execute([trim($_POST['name']), trim($_POST['description']), $cat, $price, $img, $id, $rid]);
        } else {
            db()->prepare('INSERT INTO menu_items(restaurant_id,name,description,category,price,image) VALUES(?,?,?,?,?,?)')->execute([$rid, trim($_POST['name']), trim($_POST['description']), $cat, $price, $img]);
        }
        flash('Menu item saved.');
    }
    redirect('owner/menu.php');
}
$edit = null;
if (isset($_GET['edit'])) { $q = db()->prepare('SELECT * FROM menu_items WHERE id=? AND restaurant_id=?'); $q->execute([(int)$_GET['edit'], $rid]); $edit = $q->fetch(); }
$q = db()->prepare('SELECT * FROM menu_items WHERE restaurant_id=? ORDER BY category, name'); $q->execute([$rid]); $items = $q->fetchAll();
$title = 'Menu'; require '../includes/header.php'; ?>
<div class="wrap dash"><h1>Menu Management</h1>
<div class="cols" style="margin-top:20px">
<form class="card form" method="post" enctype="multipart/form-data">
  <h3><?= $edit ? 'Edit item' : 'Add item' ?></h3><?= csrf_field() ?><input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
  <label>Name</label><input name="name" required value="<?= e($edit['name'] ?? '') ?>">
  <label>Description</label><input name="description" value="<?= e($edit['description'] ?? '') ?>">
  <label>Category (e.g. Mains, Drinks, Desserts)</label><input name="category" value="<?= e($edit['category'] ?? 'Mains') ?>">
  <label>Price (₱)</label><input type="number" step="0.01" min="1" name="price" required value="<?= e($edit['price'] ?? '') ?>">
  <label>Photo</label><input type="file" name="image" accept="image/*">
  <button class="btn"><?= $edit ? 'Update' : 'Add to menu' ?></button>
  <?php if ($edit): ?><p class="muted"><a href="menu.php">Cancel editing</a></p><?php endif; ?>
</form>
<div class="card"><h3>Your items (<?= count($items) ?>)</h3><label for="menu-item-search">Find a menu item</label><input id="menu-item-search" type="search" placeholder="Search by name or category..." data-filter-target="#owner-menu-items" data-filter-count="#owner-menu-count"><p id="owner-menu-count" class="filter-count" aria-live="polite"><?= count($items) ?> <?= count($items) === 1 ? 'result' : 'results' ?></p><div id="owner-menu-items">
<?php foreach ($items as $i): ?>
  <div class="row" data-filterable><?= thumb($i['image'], 'thumb sm') ?>
    <div><strong><?= e($i['name']) ?></strong> <span class="tag <?= $i['is_available'] ? '' : 'off' ?>"><?= $i['is_available'] ? 'Available' : 'Sold out' ?></span><br>
    <span class="muted"><?= e($i['category']) ?> · <span class="price"><?= money($i['price']) ?></span></span></div>
    <div><a href="?edit=<?= $i['id'] ?>">Edit</a>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $i['id'] ?>"><button class="link" name="action" value="toggle">Toggle</button></form>
      <form method="post" data-confirm="Delete this menu item? This cannot be undone."><?= csrf_field() ?><input type="hidden" name="id" value="<?= $i['id'] ?>"><button class="link" name="action" value="delete">Delete</button></form></div>
  </div>
<?php endforeach; if (!$items) echo '<p class="muted">No items yet.</p>'; ?>
</div></div></div></div>
<?php require '../includes/footer.php'; ?>
