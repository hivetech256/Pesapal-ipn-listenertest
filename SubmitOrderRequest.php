<?php
ob_start();  // Start output buffering

include 'conn.php'; // For DB connection
include 'RegisterIPN.php'; // For getting $token and $ipn_id

$merchantreference = mt_rand(1, 1000000000); // Random merchant reference
$phone = "0706813674";
$amount = $_POST['amount'];
$callbackurl = "https://pesapal-ipn-listenertest.onrender.com/response-page.php";
$branch = "HiveTech";
$first_name = "Njuki";
$middle_name = "Joseph";
$last_name = "Joseph";
$email_address = "njukijoseph256@gmail.com";

// Choose correct URL
if (APP_ENVIROMENT == 'sandbox') {
    $submitOrderUrl = "https://cybqa.pesapal.com/pesapalv3/api/Transactions/SubmitOrderRequest";
} elseif (APP_ENVIROMENT == 'live') {
    $submitOrderUrl = "https://pay.pesapal.com/v3/api/Transactions/SubmitOrderRequest";
} else {
    echo "Invalid APP_ENVIROMENT";
    exit;
}

// Headers
$headers = array(
    "Accept: application/json",
    "Content-Type: application/json",
    "Authorization: Bearer $token"
);

// Request body
$data = array(
    "id" => "$merchantreference",
    "currency" => "UGX",
    "amount" => $amount,
    "description" => "Payment for HiveeMovies subscription",
    "callback_url" => "$callbackurl",
    "notification_id" => "$ipn_id",
    "branch" => "$branch",
    "billing_address" => array(
        "email_address" => "$email_address",
        "phone_number" => "$phone",
        "country_code" => "UG",
        "first_name" => "$first_name",
        "middle_name" => "$middle_name",
        "last_name" => "$last_name",
        "line_1" => "Pesapal Limited",
        "line_2" => "",
        "city" => "",
        "state" => "",
        "postal_code" => "",
        "zip_code" => ""
    )
);

// Send cURL
$ch = curl_init($submitOrderUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($responseCode == 200) {
    $responseData = json_decode($response, true);
    $redirect_url = $responseData['redirect_url'];

    // Save into transactions table (only merchant_reference for now)
    $stmt = $conn->prepare("INSERT INTO transactions (merchant_reference, created_at) VALUES (?, NOW())");
    $stmt->bind_param("s", $merchantreference);
    $stmt->execute();
    $stmt->close();

    // Redirect to Pesapal payment page
    header("Location: $redirect_url");
    exit();
} else {
    echo "Error: $responseCode";
    header('Location: index.php');
    exit();
}

ob_end_flush();  // End output buffering
?>
