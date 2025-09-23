<?php
if (php_sapi_name() !== 'cli') { die("CLI only\n"); }
if ($argc < 2) { fwrite(STDERR, "Usage: php lookup_provider_email.php \"Business Name\"\n"); exit(1); }
$business = $argv[1];
require_once __DIR__ . '/config/database.php';
$pdo = getDB();
$stmt = $pdo->prepare('SELECT u.email FROM service_providers sp JOIN users u ON sp.user_id = u.id WHERE sp.business_name = ? LIMIT 1');
$stmt->execute([$business]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo $row ? $row['email'] : 'NOT_FOUND';
