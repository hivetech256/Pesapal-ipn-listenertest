<?php
// Include the access token script
require_once 'acesstoken.php'; // This sets $token
require_once 'conn.php';

// Pesapal endpoints
$status_url = (APP_ENVIROMENT === 'sandbox') 
    ? 'https://cybqa.pesapal.com/pesapalv3/api/Transactions/GetTransactionStatus'
    : 'https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus';

// Get IPN parameters
$notification_type = $_GET['pesapal_notification_type'] ?? '';
$merchant_reference = $_GET['pesapal_merchant_reference'] ?? '';
$tracking_id = $_GET['pesapal_transaction_tracking_id'] ?? '';

if ($notification_type !== 'CHANGE' || empty($tracking_id)) {
    http_response_code(400);
    echo "Invalid IPN request.";
    exit;
}

// Get transaction status from Pesapal
$headers = [
    "Authorization: Bearer $token",
    "Content-Type: application/json"
];
$postData = json_encode([
    'orderTrackingId' => $tracking_id
]);

$ch = curl_init($status_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if (!isset($data['status'])) {
    error_log("IPN Error: Invalid transaction response");
    http_response_code(500);
    exit;
}

// Extract data
$status = $data['status'];
$amount = $data['amount'];
$payment_method = $data['payment_method'] ?? '';
$currency = $data['currency'] ?? '';
$description = $data['description'] ?? '';
$payment_time = $data['payment_date'] ?? '';

// Get user_id from merchant reference like USER5-xxx
preg_match('/^USER(\d+)/', $merchant_reference, $match);
$user_id = $match[1] ?? 0;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if transaction exists
    $check = $pdo->prepare("SELECT id FROM transactions WHERE merchant_reference = ?");
    $check->execute([$merchant_reference]);
    $exists = $check->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        // Update
        $update = $pdo->prepare("UPDATE transactions SET 
            tracking_id = ?, status = ?, amount = ?, payment_method = ?, updated_at = NOW()
            WHERE merchant_reference = ?");
        $update->execute([$tracking_id, $status, $amount, $payment_method, $merchant_reference]);
    } else {
        // Insert
        $insert = $pdo->prepare("INSERT INTO transactions 
            (merchant_reference, tracking_id, status, amount, payment_method) 
            VALUES (?, ?, ?, ?, ?)");
        $insert->execute([$user_id, $merchant_reference, $tracking_id, $status, $amount, $payment_method]);
    }


    // Success response to Pesapal
    header("Content-Type: text/plain");
    echo "pesapal_notification_type=$notification_type&pesapal_transaction_tracking_id=$tracking_id&pesapal_merchant_reference=$merchant_reference";

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    exit;
}
