<?php
session_start();
include 'db.php';

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows > 0){
        $stmt->bind_result($user_id);
        $stmt->fetch();

        $_SESSION['user_id'] = $user_id;

        echo "Login successful. <a href='SubmitOrderRequest.php'>Go to Pay</a>";
    } else {
        echo "Invalid email or password.";
    }
    $stmt->close();
}
?>

<h2>Login</h2>
<form method="post" action="">
    Email: <input type="email" name="email" required><br><br>
    Password: <input type="password" name="password" required><br><br>
    <input type="submit" name="login" value="Login">
</form>
