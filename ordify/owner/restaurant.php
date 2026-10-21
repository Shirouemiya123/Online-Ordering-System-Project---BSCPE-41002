<?php
require '../config.php'; require '../includes/phase4.php'; require_role('owner');
$r = my_restaurant();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $d = [trim($_POST['name']), trim($_POST['description']), trim($_POST['address']), trim($_POST['phone']), isset($_POST['is_open']) ? 1 : 0, in_array($_POST['cuisine'] ?? '', CUISINES, true) ? $_POST['cuisine'] : 'Other'];
    if ($d[0] === '' || $d[2] === '') flash('Name and address are required.', 'error');
    else {
        $img = save_image($_FILES['image'] ?? []) ?? ($r['image'] ?? null);
        if ($r) db()->prepare('UPDATE restaurants SET name=?,description=?,address=?,phone=?,is_open=?,cuisine=?,image=? WHERE id=?')->execute([...$d, $img, $r['id']]);
        else db()->prepare('INSERT INTO restaurants(name,description,address,phone,is_open,cuisine,image,owner_id) VALUES(?,?,?,?,?,?,?,?)')->execute([...$d, $img, user()['id']]);
        flash('Restaurant saved.'); redirect('owner/restaurant.php');
    }
}
$title = 'My Restaurant'; require '../includes/header.php'; ?>
<div class="wrap"><form class="card auth form" style="max-width:560px" method="post" enctype="multipart/form-data">
  <h2>Restaurant Profile</h2><?= csrf_field() ?>
  <?= thumb($r['image'] ?? null) ?>
  <label>Restaurant name</label><input name="name" required value="<?= e($r['name'] ?? '') ?>">
  <label>Description</label><textarea name="description" rows="3"><?= e($r['description'] ?? '') ?></textarea>
  <label>Cuisine</label><select name="cuisine"><?php foreach (CUISINES as $c): ?><option <?= ($r['cuisine'] ?? 'Other') === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select>
  <label>Address</label><input name="address" required value="<?= e($r['address'] ?? '') ?>">
  <label>Phone</label><input name="phone" value="<?= e($r['phone'] ?? '') ?>">
  <label>Cover photo (JPG/PNG/WEBP, max 2MB)</label><input type="file" name="image" accept="image/*">
  <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="is_open" style="width:auto" <?= ($r['is_open'] ?? 1) ? 'checked' : '' ?>> Open for orders</label>
  <button class="btn">Save</button>
</form></div>
<?php require '../includes/footer.php'; ?>
