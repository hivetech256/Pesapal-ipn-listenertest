<?php
session_start();
include 'conn.php';
include 'RegisterIPN.php';

$merchantreference = mt_rand(1, 1000000000);
$phone = "0706813674";
$amount = $_POST['amount'];
$callbackurl = "http://hiveemovies.kesug.com/response-page.php";
$branch = "HiveTech";
$first_name = "Njuki";
$middle_name = "Joseph";
$last_name = "Joseph";
$email_address = "njukijoseph256@gmail.com";

// Choose correct URL
if(APP_ENVIROMENT == 'sandbox'){
  $submitOrderUrl = "https://cybqa.pesapal.com/pesapalv3/api/Transactions/SubmitOrderRequest";
}elseif(APP_ENVIROMENT == 'live'){
  $submitOrderUrl = "https://pay.pesapal.com/v3/api/Transactions/SubmitOrderRequest";
}else{
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
    "description" => "Payment description goes here",
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

if($responseCode == 200){
    $responseData = json_decode($response, true);
    $redirect_url = $responseData['redirect_url'];

    // Save payment details to database
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO transactions (user_id, order_tracking_id, merchant_reference) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $merchantreference, $merchantreference);
    $stmt->execute();
    $stmt->close();

    // Redirect to payment
    header("Location: $redirect_url");
    exit();
} else {
    echo "Error: $responseCode";
    header('Location:index.php');
    exit();
}
