<?php
include 'db.php';

if(isset($_POST['register'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Hash the password before saving
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $email, $hashed_password);

    if($stmt->execute()){
        echo "Registration successful. <a href='login.php'>Login Now</a>";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>

<h2>Register</h2>
<form method="post" action="">
    Email: <input type="email" name="email" required><br><br>
    Password: <input type="password" name="password" required><br><br>
    <input type="submit" name="register" value="Register">
</form>
