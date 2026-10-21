<?php
session_start();
const DB_HOST = 'localhost', DB_NAME = 'ordify', DB_USER = 'root', DB_PASS = '';
const APP_URL = 'http://localhost/ordify';
// EmailJS (server-side REST API). Keep this file private, never expose the private key in the browser.
const EMAILJS_SERVICE_ID  = 'service_knyhb6c';
const EMAILJS_TEMPLATE_ID = 'template_pzp0hwl'; // Existing account-verification template; unchanged.
const EMAILJS_RECEIPT_TEMPLATE_ID = 'template_0jz46wm'; // Separate order receipt template.
const EMAILJS_PUBLIC_KEY  = 's3X2XI-RAh4MYxvuC';
const EMAILJS_PRIVATE_KEY = 'SAlcY9s0vBpN3Pbo_WnZj';

function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function user() { return $_SESSION['user'] ?? null; }
function redirect(string $path) { header('Location: ' . APP_URL . '/' . $path); exit; }
function home_for(string $role): string { return "$role/index.php"; }
function require_role(string ...$roles) {
    $u = user();
    if (!$u || !in_array($u['role'], $roles, true)) redirect('login.php');
}
function csrf_field(): string {
    $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}
function csrf_check() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) die('Invalid request.');
}
function flash(?string $msg = null, string $type = 'info') {
    if ($msg) { $_SESSION['flash'] = [$msg, $type]; return; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function send_verification(string $to, string $name, string $link): bool {
    $log = fn($m) => file_put_contents(__DIR__ . '/storage/mail.log', "[" . date('c') . "] $m\n", FILE_APPEND);
    if (str_starts_with(EMAILJS_TEMPLATE_ID, 'PASTE')) { $log("DEV (no template ID) To: $to | $link"); return true; }
    $ch = curl_init('https://api.emailjs.com/api/v1.0/email/send');
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode([
            'service_id' => EMAILJS_SERVICE_ID, 'template_id' => EMAILJS_TEMPLATE_ID,
            'user_id' => EMAILJS_PUBLIC_KEY, 'accessToken' => EMAILJS_PRIVATE_KEY,
            'template_params' => ['to_name' => $name, 'to_email' => $to, 'verify_link' => $link],
        ]),
    ]);
    $res = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($code !== 200) $log("EmailJS FAILED ($code) To: $to | $res | link: $link");
    return $code === 200;
}

/** Send the order receipt using the separate EmailJS receipt template. */
function send_order_receipt(array $customer, array $order, array $items): bool {
    $log = static function (string $message): void {
        $dir = __DIR__ . '/storage';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        @file_put_contents($dir . '/mail.log', '[' . date('c') . '] ' . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
    };

    $email = trim((string)($customer['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $log('Order receipt skipped: customer email is missing or invalid.');
        return false;
    }
    if (!function_exists('curl_init')) {
        $log('Order receipt failed: PHP cURL extension is disabled.');
        return false;
    }

    $createdAt = (string)($order['created_at'] ?? date('Y-m-d H:i:s'));
    $timestamp = strtotime($createdAt);
    $formattedItems = [];
    foreach ($items as $item) {
        $qty = max(1, (int)($item['qty'] ?? 1));
        $name = trim((string)($item['name'] ?? 'Item'));
        $lineTotal = (float)($item['price'] ?? 0) * $qty;
        $formattedItems[] = sprintf('%d x %s — ₱%s', $qty, $name, number_format($lineTotal, 2));
    }

    $params = [
        'to_email' => $email,
        'email' => $email,
        'customer_name' => (string)($customer['name'] ?? 'ORDIFY Customer'),
        'order_id' => (string)($order['id'] ?? ''),
        'order_date' => date('F j, Y', $timestamp ?: time()),
        'order_time' => date('g:i A', $timestamp ?: time()),
        'restaurant_name' => (string)($order['restaurant_name'] ?? 'ORDIFY Restaurant'),
        'order_items' => implode("\n", $formattedItems),
        'subtotal' => number_format((float)($order['subtotal'] ?? 0), 2),
        'delivery_fee' => number_format((float)($order['delivery_fee'] ?? 0), 2),
        'total_amount' => number_format((float)($order['total'] ?? 0), 2),
        'delivery_address' => (string)($order['address'] ?? ''),
        'payment_method' => (string)($order['payment_method'] ?? 'Cash on Delivery'),
        'order_status' => (string)($order['status'] ?? 'Placed'),
    ];

    $payload = [
        'service_id' => EMAILJS_SERVICE_ID,
        'template_id' => EMAILJS_RECEIPT_TEMPLATE_ID,
        'user_id' => EMAILJS_PUBLIC_KEY,
        'accessToken' => EMAILJS_PRIVATE_KEY,
        'template_params' => $params,
    ];
    $ch = curl_init('https://api.emailjs.com/api/v1.0/email/send');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        $log('Order receipt EmailJS failed: HTTP=' . $httpCode . '; cURL=' . $curlError . '; response=' . (string)$response);
        return false;
    }
    $log('Order receipt sent successfully for order #' . (string)($order['id'] ?? 'unknown') . '.');
    return true;
}

const DELIVERY_FEE = 49;
function money($v): string { return '₱' . number_format((float)$v, 2); }
function cart_count(): int { return array_sum($_SESSION['cart']['items'] ?? []); }
function thumb(?string $f, string $cls = 'thumb'): string {
    return $f ? '<img class="' . $cls . '" src="' . APP_URL . '/uploads/' . e($f) . '" alt="">' : '<div class="' . $cls . ' ph"></div>';
}
function save_image(array $f): ?string {
    if (($f['error'] ?? 4) !== UPLOAD_ERR_OK || $f['size'] > 2 * 1024 * 1024) return null;
    $i = @getimagesize($f['tmp_name']);
    $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$i[2] ?? 0] ?? null;
    if (!$ext) return null;
    $n = bin2hex(random_bytes(8)) . ".$ext";
    return move_uploaded_file($f['tmp_name'], __DIR__ . "/uploads/$n") ? $n : null;
}
function my_restaurant() {
    $q = db()->prepare('SELECT * FROM restaurants WHERE owner_id=?'); $q->execute([user()['id']]);
    return $q->fetch();
}

const ORDER_FLOW = ['placed' => 'Placed', 'accepted' => 'Accepted', 'preparing' => 'Preparing', 'ready' => 'Ready for pickup', 'picked_up' => 'On the way', 'delivered' => 'Delivered'];
function status_tag(string $s): string {
    $l = ORDER_FLOW[$s] ?? 'Cancelled';
    return '<span class="tag ' . ($s === 'cancelled' ? 'off' : '') . '">' . e($l) . '</span>';
}
const LINES_SQL = "(SELECT GROUP_CONCAT(CONCAT(qty,'× ',name) SEPARATOR ', ') FROM order_items WHERE order_id=o.id) AS `lines`";
