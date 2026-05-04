<?php
include "DBConn.php";
session_start();

$msg = "";

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM tblAdmin WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $admin = $result->fetch_assoc();

        if(password_verify($password, $admin['password'])){
            $_SESSION['adminID'] = $admin['adminID'];
            header("Location: adminDashboard.php");
            exit();
        } else {
            $msg = "Incorrect password!";
        }
    } else {
        $msg = "Admin not found!";
    }
}
?>

<link rel="stylesheet" href="style.css">

<div class="auth-container">

    <!-- LEFT SIDE (IMAGE / HERO) -->
    <div class="auth-hero">
        <div>
            <h2>Admin Panel</h2>
            <p>Manage users, products and orders</p>
        </div>
    </div>

    <!-- RIGHT SIDE (FORM) -->
    <div class="auth-card">
        <h2>Admin Login</h2>

        <?php if($msg) echo "<div class='errors'>$msg</div>"; ?>

        <form method="POST">

            <div class="form-row">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-row">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-actions">
                <input type="submit" name="login" value="Login" class="btn">
            </div>

        </form>

        <a href="login.php">Back to User Login</a>
    </div>

</div>