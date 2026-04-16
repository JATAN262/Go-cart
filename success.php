<?php
include 'config.php';

$secretKey = "0678056d96914a8583fb518caf42828a"; // Same key used for checksum

// Capture Zaakpay response
$response = $_POST;
if (!$response) {
    echo "No response received.";
    exit;
}

// Extract checksum & prepare for validation
$receivedChecksum = $response['checksum'];
unset($response['checksum']);

// Recalculate checksum
ksort($response);
$data = '';
foreach ($response as $value) {
    $data .= $value;
}
$calculatedChecksum = hash_hmac("sha256", $data, $secretKey);

if ($receivedChecksum !== $calculatedChecksum) {
    echo "Checksum mismatch. Possible tampering!";
    exit;
}

// Check payment status
$paymentStatus = $response['responseCode']; // '100' means success
$orderId = $response['orderId'];

$db = new Database();

if ($paymentStatus == '100') {
    // Update payment status
    $db->update('payments', ['payment_status' => 'credit'], "txn_id = '$orderId'");
    echo "<h3>Payment Successful</h3>";
} else {
    $db->update('payments', ['payment_status' => 'failed'], "txn_id = '$orderId'");
    echo "<h3>Payment Failed: " . $response['responseDescription'] . "</h3>";
}
?>
