<?php
// Run ONCE in browser: http://localhost/ordify/setup_admin.php  then DELETE this file.
require 'config.php';
db()->prepare("INSERT IGNORE INTO users(name,email,password,role,status,email_verified) VALUES('Administrator','admin@ordify.com',?,'admin','active',1)")
    ->execute([password_hash('Admin@12345', PASSWORD_DEFAULT)]);
echo 'Admin ready: admin@ordify.com / Admin@12345. Delete this file now.';
