<?php
session_start();
require_once 'config/database.php';
require_once 'includes/mtn_momo.php';
require_once 'includes/orange_money.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $user_id = $_SESSION['user_id'];
    $request_id = 1; // Replace with actual request/order ID

    $db = getDB();

    if ($payment_method === 'mobile_money') {
        $payer_number = $_POST['payer_number'];
        $external_id = uniqid('order_');
        $mtn = new MTNMomo();
        $referenceId = $mtn->requestToPay($amount, 'EUR', $external_id, $payer_number, 'Payment for service', 'Thank you!');
        $stmt = $db->prepare("INSERT INTO payments (request_id, amount, payment_method, status, transaction_id) VALUES (?, ?, ?, 'pending', ?)");
        $stmt->execute([$request_id, $amount, 'mobile_money', $referenceId]);
        header("Location: mtn_payment_status.php?ref=$referenceId");
        exit();
    } elseif ($payment_method === 'orange_money') {
        $payer_number = $_POST['payer_number'];
        $external_id = uniqid('order_');
        $orange = new OrangeMoney();
        $referenceId = $orange->requestToPay($amount, 'EUR', $external_id, $payer_number, 'Payment for service', 'Thank you!');
        $stmt = $db->prepare("INSERT INTO payments (request_id, amount, payment_method, status, transaction_id) VALUES (?, ?, ?, 'pending', ?)");
        $stmt->execute([$request_id, $amount, 'orange_money', $referenceId]);
        header("Location: orange_payment_status.php?ref=$referenceId");
        exit();
    } elseif ($payment_method === 'cash') {
        $stmt = $db->prepare("INSERT INTO payments (request_id, amount, payment_method, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$request_id, $amount, 'cash']);
        header("Location: dashboard.php?msg=Cash payment will be collected on service delivery.");
        exit();
    } elseif ($payment_method === 'bank_transfer') {
        $stmt = $db->prepare("INSERT INTO payments (request_id, amount, payment_method, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$request_id, $amount, 'bank_transfer']);
        header("Location: bank_transfer_instructions.php?amount=$amount");
        exit();
    } elseif ($payment_method === 'card') {
        $stmt = $db->prepare("INSERT INTO payments (request_id, amount, payment_method, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$request_id, $amount, 'card']);
        header("Location: card_payment.php?amount=$amount");
        exit();
    }
}
?> 