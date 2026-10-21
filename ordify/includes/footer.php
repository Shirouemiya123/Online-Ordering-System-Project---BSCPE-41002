</main>
<footer class="foot"><div class="wrap">&copy; <?= date('Y') ?> ORDIFY. Cash on Delivery only. Fresh food, delivered.</div></footer>
<?php $footerScriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''); $footerIsAdminPage = (($u['role'] ?? '') === 'admin') || str_contains($footerScriptPath, '/admin/'); if (!$footerIsAdminPage): ?>
<script src="<?= APP_URL ?>/assets/js/ordify.js" defer></script>
<?php endif; ?>
</body></html>
