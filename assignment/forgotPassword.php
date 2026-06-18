<?php
$msg = "";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);

    if (empty($email)) {
        $errors[] = "Email is required.";
    } else {
        // Fake success (not functional)
        $msg = "If this email exists, a reset link has been sent.";
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
            <h1>Reset Password</h1>
            <p>Enter your email to receive a reset link</p>
        </div>
    </div>

    <div class="auth-card">

        <h2>Forgot Password</h2>

        <?php if(!empty($errors)): ?>
        <div class="errors">
            <?php foreach($errors as $e) echo "<div>$e</div>"; ?>
        </div>
        <?php endif; ?>

        <?php if($msg): ?>
        <div class="success"><?= $msg ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-row">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-actions">
                <button class="btn">Send Reset Link</button>
            </div>

        </form>

        <div style="margin-top:12px;text-align:center">
            <a href="login.php">Back to Login</a>
        </div>

    </div>

</div>

</body>
</html>