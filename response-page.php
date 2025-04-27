<?php
include 'conn.php'; // DB connection
include 'RegisterIPN.php'; // For getting $token

if (!isset($_GET['orderTrackingId'])) {
    echo "Invalid request. No orderTrackingId.";
    exit();
}

$orderTrackingId = $_GET['orderTrackingId'];

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

// Debugging: Output the response for analysis
if ($responseCode == 200) {
    $responseData = json_decode($response, true);

    // Check the response content
    echo "<pre>";
    print_r($responseData);
    echo "</pre>";

    // Extract payment details from the response
    $payment_method = isset($responseData['payment_method']) ? $responseData['payment_method'] : '';
    $amount = isset($responseData['amount']) ? $responseData['amount'] : 0;
    $status = isset($responseData['payment_status']) ? $responseData['payment_status'] : 'Unknown';
    $merchant_reference = isset($responseData['merchant_reference']) ? $responseData['merchant_reference'] : '';

    // Check if values exist before updating in the database
    if (!empty($merchant_reference)) {
        // Check if a record with the same merchant_reference exists
        $stmt = $conn->prepare("SELECT id FROM transactions WHERE merchant_reference = ?");
        $stmt->bind_param("s", $merchant_reference);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Record exists, update it
            $stmt = $conn->prepare("UPDATE transactions SET order_tracking_id = ?, payment_method = ?, amount = ?, status = ?, updated_at = NOW() WHERE merchant_reference = ?");
            $stmt->bind_param("ssdss", $orderTrackingId, $payment_method, $amount, $status, $merchant_reference);
            $stmt->execute();
            $stmt->close();

            echo "<h2>Payment Updated</h2>";
            echo "<p>Status: $status</p>";
            echo "<p>Amount Paid: UGX $amount</p>";
            echo "<a href='index.php'>Back to Home</a>";
        } else {
            // Record does not exist, insert a new one
            $stmt = $conn->prepare("INSERT INTO transactions (merchant_reference, order_tracking_id, payment_method, amount, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param("sssds", $merchant_reference, $orderTrackingId, $payment_method, $amount, $status);
            $stmt->execute();
            $stmt->close();

            echo "<h2>Payment Inserted</h2>";
            echo "<p>Status: $status</p>";
            echo "<p>Amount Paid: UGX $amount</p>";
            echo "<a href='index.php'>Back to Home</a>";
        }

    } else {
        echo "Invalid merchant_reference. No update performed.";
    }

} else {
    echo "Failed to fetch payment details. HTTP Code: $responseCode";
}
?>
