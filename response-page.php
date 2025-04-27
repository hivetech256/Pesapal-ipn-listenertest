<?php
include 'db.php'; // DB connection
include 'RegisterIPN.php'; // For getting $token

// Check if the OrderTrackingId is passed in the URL
if (!isset($_GET['OrderTrackingId'])) {
    echo "Invalid request. No OrderTrackingId.";
    exit();
}

$orderTrackingId = $_GET['OrderTrackingId']; // Get the OrderTrackingId from the URL

// Fetch the payment status from Pesapal API
$url = "";

if (APP_ENVIROMENT == 'sandbox') {
    $url = "https://cybqa.pesapal.com/pesapalv3/api/Transactions/GetTransactionStatus?orderTrackingId=$orderTrackingId";
} elseif (APP_ENVIROMENT == 'live') {
    $url = "https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus?orderTrackingId=$orderTrackingId";
} else {
    echo "Invalid APP_ENVIROMENT";
    exit();
}

$headers = array(
    "Accept: application/json",
    "Authorization: Bearer $token"
);

// cURL to fetch payment status
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($responseCode == 200) {
    $responseData = json_decode($response, true);

    // Extract payment details from the response
    $payment_method = isset($responseData['payment_method']) ? $responseData['payment_method'] : '';
    $amount = isset($responseData['amount']) ? $responseData['amount'] : 0;
    $status = isset($responseData['payment_status_description']) ? $responseData['payment_status_description'] : 'Unknown';
    $merchant_reference = isset($responseData['merchant_reference']) ? $responseData['merchant_reference'] : '';
    $order_tracking_id = isset($responseData['order_tracking_id']) ? $responseData['order_tracking_id'] : '';
    $created_at = isset($responseData['created_date']) ? $responseData['created_date'] : date('Y-m-d H:i:s'); // Default to current time
    $updated_at = date('Y-m-d H:i:s'); // Default to current time

    // Check if the transaction already exists
    $stmt = $conn->prepare("SELECT id FROM transactions WHERE merchant_reference = ?");
    $stmt->bind_param("s", $merchant_reference);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // If the transaction already exists, update it
        $stmt = $conn->prepare("UPDATE transactions SET order_tracking_id = ?, payment_method = ?, amount = ?, status = ?, created_at = ?, updated_at = ? WHERE merchant_reference = ?");
        $stmt->bind_param("ssdssss", $order_tracking_id, $payment_method, $amount, $status, $created_at, $updated_at, $merchant_reference);
        $stmt->execute();
        echo "<h2>Payment details updated successfully.</h2>";
    } else {
        // If the transaction doesn't exist, insert a new record
        $stmt = $conn->prepare("INSERT INTO transactions (merchant_reference, order_tracking_id, payment_method, amount, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $merchant_reference, $order_tracking_id, $payment_method, $amount, $status, $created_at, $updated_at);
        $stmt->execute();
        echo "<h2>Payment details inserted successfully.</h2>";
    }
    
    $stmt->close();
    
    // Display the payment status and details
    echo "<p>Status: $status</p>";
    echo "<p>Amount Paid: UGX $amount</p>";
    echo "<a href='index.php'>Back to Home</a>";

} else {
    echo "Failed to fetch payment details. HTTP Code: $responseCode";
}
?>
