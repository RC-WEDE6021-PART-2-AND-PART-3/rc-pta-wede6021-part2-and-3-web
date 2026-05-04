<?php
session_start();
include "DBConn.php";

$errors = [];

if (isset($_SESSION['userID'])) {
    header('Location: shop.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $errors[] = "All fields are required.";
    } else {

        // Detect email or username
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $stmt = $conn->prepare("SELECT * FROM tblUser WHERE email=?");
        } else {
            $stmt = $conn->prepare("SELECT * FROM tblUser WHERE username=?");
        }

        $stmt->bind_param("s", $login);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                if ($user['status'] !== 'verified') {
                    $errors[] = "Account not verified yet.";
                } else {
                    $_SESSION['userID'] = $user['userID'];
                    $_SESSION['name'] = $user['fullname'];
                    $_SESSION['user'] = [
                        'name' => $user['fullname'],
                        'role' => $user['role'] ?? 'user'
                    ];
                    header("Location: shop.php");
                    exit;
                }

            } else {
                $errors[] = "Invalid login or password.";
            }

        } else {
            $errors[] = "User not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="auth-container">

<div class="auth-hero">
    <div>
        <h1>Welcome Back</h1>
        <p style="opacity:0.9;max-width:360px;margin:12px auto">
            Sign in to manage orders, track purchases, 
            or sell your products on Pastimes.</p>
    </div>
</div>

<div class="auth-card">

<h2>Sign In</h2>

<?php if(!empty($errors)): ?>
<div class="errors">
<?php foreach($errors as $e) echo "<div>$e</div>"; ?>
</div>
<?php endif; ?>

<form method="POST">

<div class="form-row">
<label>Username or Email</label>
<input type="text" name="login" required
value="<?= $_POST['login'] ?? '' ?>">
</div>

<div class="form-row">
<label>Password</label>
<input type="password" name="password" required>
</div>
<div style="text-align:right; margin-top:5px;">
    <a href="forgotPassword.php">Forgot Password?</a>
</div>
<div class="form-actions">
<button class="btn">Login</button>
<div style="margin-left:auto">New? <a href="register.php">Register</a></div>
</div>

</form>

</div>
</div>

</body>
</html>