<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pay with Mobile Money, Card, or Bank</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card mx-auto" style="max-width: 400px;">
        <div class="card-body">
            <h3 class="mb-4 text-center">Make a Payment</h3>
            <form method="post" action="process_payment.php">
                <div class="form-group">
                    <label>Amount</label>
                    <input type="number" name="amount" class="form-control" required min="1" step="0.01">
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" class="form-control" required id="payment-method-select">
                        <option value="mobile_money">MTN MoMo</option>
                        <option value="orange_money">Orange Money</option>
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="card">Card</option>
                    </select>
                </div>
                <div class="form-group" id="mobile-money-fields">
                    <label>Phone Number (MSISDN)</label>
                    <input type="text" name="payer_number" class="form-control" placeholder="e.g. 256771234567">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Pay Now</button>
            </form>
        </div>
    </div>
</div>
<script>
const methodSelect = document.getElementById('payment-method-select');
const mobileFields = document.getElementById('mobile-money-fields');
function toggleMobileFields() {
    mobileFields.style.display = (methodSelect.value === 'mobile_money' || methodSelect.value === 'orange_money') ? 'block' : 'none';
}
methodSelect.addEventListener('change', toggleMobileFields);
toggleMobileFields();
</script>
</body>
</html> 