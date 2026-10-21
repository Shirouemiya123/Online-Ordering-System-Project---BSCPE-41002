<?php
require 'config.php';
$t = $_GET['token'] ?? '';
$q = db()->prepare('UPDATE users SET email_verified=1, verify_token=NULL WHERE verify_token=? AND LENGTH(?)=64');
$q->execute([$t, $t]);
$q->rowCount() ? flash('Email verified! You can now log in.') : flash('Invalid or expired verification link.', 'error');
redirect('login.php');
