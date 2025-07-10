<?php
session_start();
require_once 'includes/mtn_momo.php';
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];
    $payer_number = $_POST['payer_number'];
    $user_id = $_SESSION['user_id'];
    $external_id = uniqid('order_');

    $mtn = new MTNMomo();
    $referenceId = $mtn->requestToPay($amount, 'EUR', $external_id, $payer_number, 'Payment for service', 'Thank you!');

    // Save to DB as pending
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO payments (user_id, amount, payment_method, status, reference_id, external_id, payer_number) VALUES (?, ?, 'mtn', 'pending', ?, ?, ?)");
    $stmt->execute([$user_id, $amount, $referenceId, $external_id, $payer_number]);

    // Redirect to status page
    header("Location: mtn_payment_status.php?ref=$referenceId");
    exit();
}
?> 