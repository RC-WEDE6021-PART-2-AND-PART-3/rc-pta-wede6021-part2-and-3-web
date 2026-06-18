<?php
include "DBConn.php";

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    // Validation
    if (!$name || !$username || !$email || !$password || !$confirm) {
        $errors[] = "All fields are required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    // Check duplicates
    $check = $conn->prepare("SELECT * FROM tblUser WHERE email=? OR username=?");
    $check->bind_param("ss", $email, $username);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $errors[] = "Username or Email already exists.";
    }

    // If no errors → insert
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO tblUser(fullname, username, email, password, status)
                                VALUES(?,?,?,?, 'pending')");
        $stmt->bind_param("ssss", $name, $username, $email, $hashed);

        if ($stmt->execute()) {
            $success = "Registered successfully! Wait for admin approval.";
        } else {
            $errors[] = "Something went wrong.";
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
            <h1 style="margin:0">Create your account</h1>
            <p style="opacity:0.9;max-width:360px;margin:12px auto">Join Pastimes to save orders, speed checkout, and get early access to drops.</p>
        </div>
    </div>

<div class="auth-card">

<h2>Register</h2>

<?php if(!empty($errors)): ?>
<div class="errors">
<?php foreach($errors as $e) echo "<div>$e</div>"; ?>
</div>
<?php endif; ?>

<?php if($success): ?>
<div class="success"><?= $success ?></div>
<?php endif; ?>

<form method="POST">

<div class="form-row">
<label>Full Name</label>
<input type="text" name="fullname" required value="<?= $_POST['fullname'] ?? '' ?>">
</div>

<div class="form-row">
<label>Username</label>
<input type="text" name="username" required value="<?= $_POST['username'] ?? '' ?>">
</div>

<div class="form-row">
<label>Email</label>
<input type="email" name="email" required value="<?= $_POST['email'] ?? '' ?>">
</div>

<div class="form-row">
<label>Password</label>
<input type="password" name="password" required>
</div>

<div class="form-row">
<label>Confirm Password</label>
<input type="password" name="confirm" required>
</div>

<div class="form-actions">
<button class="btn">Register</button>
<div style="margin-left:auto">Already have account? <a href="login.php">Login</a></div>
</div>

</form>

    </div>
</div>

</body>
</html>