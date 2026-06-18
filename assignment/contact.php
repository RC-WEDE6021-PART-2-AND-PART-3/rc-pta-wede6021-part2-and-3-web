<?php
$success = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $message = htmlspecialchars(trim($_POST['message']));

    if(empty($name) || empty($email) || empty($message)){
        $errors[] = "All fields are required.";
    }

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $errors[] = "Invalid email format.";
    }

    if(empty($errors)){
        // Demo only (no actual sending)
        $success = "Message sent successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->
    <div class="taskbar">
        <h2>Contact</h2>
        <div class="tb-nav">
            <a href="index.php">Home</a>
            <a href="shop.php">Shop</a>
            <a href="cart.php">Cart</a>
            <a href="contact.php">Contact</a>
            <a href="login.php">Login</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <div class="auth-container">

            <!-- LEFT SIDE -->
            <div class="auth-hero">
                <div>
                    <h1>Contact Us</h1>
                    <p>We'd love to hear from you</p>
                </div>
            </div>

            <!-- RIGHT SIDE -->
            <div class="auth-card">

                <h2>Send Message</h2>

                <!-- ERRORS -->
                <?php if(!empty($errors)): ?>
                <div class="errors">
                    <?php foreach($errors as $e) echo "<div>$e</div>"; ?>
                </div>
                <?php endif; ?>

                <!-- SUCCESS -->
                <?php if($success): ?>
                <div class="success"><?= $success ?></div>
                <?php endif; ?>

                <form method="POST">

                    <div class="form-row">
                        <label>Name</label>
                        <input type="text" name="name" required value="<?= $_POST['name'] ?? '' ?>">
                    </div>

                    <div class="form-row">
                        <label>Email</label>
                        <input type="email" name="email" required value="<?= $_POST['email'] ?? '' ?>">
                    </div>

                    <div class="form-row">
                        <label>Message</label>
                        <textarea name="message" required style="width:100%;padding:10px;border-radius:6px;background:#0d0d0d;color:white;border:1px solid #333;"><?= $_POST['message'] ?? '' ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button class="btn">Send</button>
                    </div>

                </form>

            </div>

        </div>

    </div>

<!-- FOOTER -->
<footer>
    <p>© 2026 Pastimes Clothing</p>
</footer>

</div>

</body>
</html>