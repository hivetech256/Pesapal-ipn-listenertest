<?php
session_start();
include 'conn.php';

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows > 0){
        $stmt->bind_result($user_id, $hashed_password);
        $stmt->fetch();

        if(password_verify($password, $hashed_password)){
            $_SESSION['user_id'] = $user_id;
            echo "Login successful. <a href='SubmitOrderRequest.php'>Go to Pay</a>";
        } else {
            echo "Invalid email or password.";
        }
    } else {
        echo "Invalid email or password.";
    }
    $stmt->close();
}
?>

