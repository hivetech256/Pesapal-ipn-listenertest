 <?php
// if(!isset($_SESSION['user_id'])){
//     echo "Please login first.";
//     exit;
// }
// // Now user is logged in, you can proceed
// $user_id = $_SESSION['user_id'];
// ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
<form action="SubmitOrderRequest.php" method="POST">
    <label>Amount:</label>
    <input type="text" name="amount" required>
    
    <button type="submit">Pay Now</button>
</form>
<p><a href="login.php">login</a> Or <a href="register.php">Register</a><p>
</body>
</html>
