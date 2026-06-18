<?php
session_start();
include "DBConn.php";

// Redirect if not logged in
if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit;
}

$userID = $_SESSION['userID'];
$message = '';
$error = '';
$sellersFile = __DIR__ . '/sellers.json';
$productsFile = __DIR__ . '/sellers_products.json';
$uploadDir = 'images/sellers/';

// Load existing sellers and products (with validation)
$sellers = [];
if (file_exists($sellersFile)) {
    $content = file_get_contents($sellersFile);
    $decoded = json_decode($content, true);
    $sellers = (is_array($decoded)) ? $decoded : [];
}

$products = [];
if (file_exists($productsFile)) {
    $content = file_get_contents($productsFile);
    $decoded = json_decode($content, true);
    $products = (is_array($decoded)) ? $decoded : [];
}

// Check if user is already a seller
$isSeller = false;
$sellerIndex = null;
$approved = false;

foreach ($sellers as $i => $s) {
    if (isset($s['userID']) && $s['userID'] == $userID) {
        $isSeller = true;
        $sellerIndex = $i;
        $approved = $s['approved'] ?? false;
        break;
    }
}

// Handle registration/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'register') {
        $store_name = trim($_POST['store_name'] ?? '');
        $owner_name = trim($_POST['owner_name'] ?? '');
        $bank_name = trim($_POST['bank_name'] ?? '');
        $account_name = trim($_POST['account_name'] ?? '');
        $account_number = trim($_POST['account_number'] ?? '');

        // Validation
        if (empty($store_name) || empty($owner_name)) {
            $error = "Store name and owner name are required.";
        } elseif (empty($bank_name) || empty($account_name) || empty($account_number)) {
            $error = "All bank details are required.";
        } elseif (!ctype_digit($account_number) || strlen($account_number) < 6 || strlen($account_number) > 20) {
            $error = "Account number must be numeric and 6–20 digits.";
        } else {
            // Create or update seller record
            $sellerData = [
                'userID' => $userID,
                'email' => $_SESSION['name'] ?? $_SESSION['userID'],
                'store_name' => $store_name,
                'owner_name' => $owner_name,
                'bank_name' => $bank_name,
                'account_name' => $account_name,
                'account_number' => $account_number,
                'approved' => false,
                'created_at' => $isSeller ? ($sellers[$sellerIndex]['created_at'] ?? date('c')) : date('c')
            ];

            if ($isSeller) {
                $sellers[$sellerIndex] = $sellerData;
                $message = "Seller profile updated. Waiting for admin approval.";
                $approved = false;
            } else {
                $sellers[] = $sellerData;
                $isSeller = true;
                $sellerIndex = count($sellers) - 1;
                $message = "Seller application submitted! Waiting for admin approval.";
            }

            if (!@file_put_contents($sellersFile, json_encode($sellers, JSON_PRETTY_PRINT))) {
                $error = "Failed to save seller information. Please try again.";
            }
        }
    }
    
    // Handle product upload (only for approved sellers)
    if (isset($_POST['action']) && $_POST['action'] === 'upload_product' && $approved) {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        
        $imgPath = '';
        if (!empty($_FILES['image']['name'])) {
            $f = $_FILES['image'];
            $allowed = ['jpg','jpeg','png','gif'];
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if ($f['error'] !== UPLOAD_ERR_OK) {
                $error = 'Image upload error.';
            } elseif (!in_array($ext, $allowed)) {
                $error = 'Invalid image type.';
            } elseif ($f['size'] > 5 * 1024 * 1024) {
                $error = 'Image too large (max 5MB).';
            } else {
                @mkdir(__DIR__ . '/' . $uploadDir, 0755, true);
                $fname = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $dest = __DIR__ . '/' . $uploadDir . $fname;
                if (move_uploaded_file($f['tmp_name'], $dest)) {
                    $imgPath = $uploadDir . $fname;
                } else {
                    $error = 'Failed to save image.';
                }
            }
        }
        
        if ($error === '') {
            $entry = [
                'name' => $name,
                'description' => $desc,
                'price' => $price,
                'img' => $imgPath,
                'seller_email' => $_SESSION['name'] ?? $_SESSION['userID'],
                'seller_name' => $sellers[$sellerIndex]['store_name'],
                'created_at' => date('c')
            ];
            $products[] = $entry;
            if (!@file_put_contents($productsFile, json_encode($products, JSON_PRETTY_PRINT))) {
                $error = "Product uploaded but failed to save to database. Please contact support.";
            } else {
                $message = 'Product uploaded successfully!';
            }
        }
    }
    
    // Handle product deletion (only for approved sellers)
    if (isset($_POST['action']) && $_POST['action'] === 'delete_product' && $approved) {
        $idx = (int)($_POST['product_index'] ?? -1);
        $sellerName = $sellers[$sellerIndex]['store_name'];
        if (isset($products[$idx]) && ($products[$idx]['seller_name'] ?? '') === $sellerName) {
            if (!empty($products[$idx]['img']) && file_exists(__DIR__ . '/' . $products[$idx]['img'])) {
                @unlink(__DIR__ . '/' . $products[$idx]['img']);
            }
            array_splice($products, $idx, 1);
            if (!@file_put_contents($productsFile, json_encode($products, JSON_PRETTY_PRINT))) {
                $error = "Failed to delete product. Please try again.";
            } else {
                $message = 'Product removed successfully.';
            }
        }
    }
}

// Get current seller data if exists
$currentSeller = $isSeller && $sellerIndex !== null ? $sellers[$sellerIndex] : null;

// Build list of this seller's products
$myProducts = [];
if ($isSeller && $currentSeller) {
    foreach ($products as $i => $p) {
        if (($p['seller_name'] ?? '') === $currentSeller['store_name']) {
            $myProducts[$i] = $p;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Seller | Pastimes</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
    <aside class="taskbar">
        <div class="tb-top">
            <img src="images/logo.jpeg" alt="Pastimes logo" class="tb-logo-img">
            <h2 class="tb-title">Pastimes</h2>
        </div>

        <nav class="tb-nav">
            <a href="index.php">Home</a>
            <a href="shop.php">Shop</a>
            <a href="cart.php">Cart</a>
            <a href="contact.php">Contact</a>
            <?php if (!empty($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'seller'): ?>
                <a href="seller_dashboard.php">Seller Dashboard</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </nav>

        <div class="tb-user">
            <?php if (!empty($_SESSION['name'])): ?>
                Hello, <?= htmlspecialchars($_SESSION['name']) ?>
            <?php else: ?>
                Logged in
            <?php endif; ?>
        </div>
    </aside>

    <main class="main-content">
        <h1 class="title">
            <?php
            if (!$isSeller) {
                echo 'Become a Seller';
            } elseif ($approved) {
                echo 'Seller Dashboard';
            } else {
                echo 'Seller Application';
            }
            ?>
        </h1>

        <?php if ($message): ?>
            <div class="success" style="max-width:900px;margin:10px auto;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="errors" style="max-width:900px;margin:10px auto;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- PENDING APPROVAL -->
        <?php if ($isSeller && !$approved): ?>
            <div style="max-width:600px;margin:30px auto;background:#1a1a1a;padding:30px;border-radius:10px;text-align:center;border:2px solid #ff9800;">
                <h2>Waiting for Approval</h2>
                <p>Your seller application is under review. An admin will approve your account shortly.</p>
                <p style="color:#aaa;font-size:0.9em;">Store: <strong><?= htmlspecialchars($currentSeller['store_name'] ?? '') ?></strong></p>
                <p style="color:#aaa;font-size:0.9em;">Submitted: <strong><?= htmlspecialchars($currentSeller['created_at'] ?? '') ?></strong></p>
            </div>

        <!-- APPROVED - SELLER DASHBOARD -->
        <?php elseif ($approved): ?>
            <div style="max-width:900px;margin:30px auto;">
                <!-- Bank Details -->
                <div style="background:#1a1a1a;padding:20px;border-radius:10px;margin-bottom:20px;">
                    <h3>Bank Details</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="register">
                        <div class="form-row">
                            <label>Store Name</label>
                            <input type="text" name="store_name" required value="<?= htmlspecialchars($currentSeller['store_name'] ?? '') ?>">
                        </div>
                        <div class="form-row">
                            <label>Owner Name</label>
                            <input type="text" name="owner_name" required value="<?= htmlspecialchars($currentSeller['owner_name'] ?? '') ?>">
                        </div>
                        <div class="form-row">
                            <label>Bank Name</label>
                            <input type="text" name="bank_name" required value="<?= htmlspecialchars($currentSeller['bank_name'] ?? '') ?>">
                        </div>
                        <div class="form-row">
                            <label>Account Holder Name</label>
                            <input type="text" name="account_name" required value="<?= htmlspecialchars($currentSeller['account_name'] ?? '') ?>">
                        </div>
                        <div class="form-row">
                            <label>Account Number (6-20 digits)</label>
                            <input type="text" name="account_number" required value="<?= htmlspecialchars($currentSeller['account_number'] ?? '') ?>">
                        </div>
                        <div class="form-actions">
                            <button class="btn" type="submit">Update Details</button>
                        </div>
                    </form>
                </div>

                <!-- Upload Product -->
                <div style="background:#1a1a1a;padding:20px;border-radius:10px;margin-bottom:20px;">
                    <h3>Upload Product</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_product">
                        <div class="form-row">
                            <label>Product Name</label>
                            <input type="text" name="name" placeholder="Product name" required>
                        </div>
                        <div class="form-row">
                            <label>Description</label>
                            <textarea name="description" placeholder="Short description" rows="3"></textarea>
                        </div>
                        <div class="form-row">
                            <label>Price</label>
                            <input type="number" step="0.01" name="price" placeholder="Price" required>
                        </div>
                        <div class="form-row">
                            <label>Product Image</label>
                            <input id="imageInput" type="file" name="image" accept="image/*">
                            <div style="margin-top:8px"><img id="preview" src="" style="max-width:180px;display:none;border-radius:6px;border:1px solid #222;"></div>
                            <div style="font-size:0.85em;color:#aaa;">Max 5MB. Allowed: jpg, jpeg, png, gif.</div>
                        </div>
                        <div class="form-actions">
                            <button class="btn" type="submit">Upload Product</button>
                        </div>
                    </form>
                </div>

                <!-- Your Products -->
                <div style="background:#1a1a1a;padding:20px;border-radius:10px;">
                    <h3>Your Products</h3>
                    <?php if (empty($myProducts)): ?>
                        <p style="color:#aaa;">No products uploaded yet.</p>
                    <?php else: ?>
                        <table style="width:100%;border-collapse:collapse;">
                            <thead>
                                <tr style="border-bottom:2px solid #333;">
                                    <th style="text-align:left;padding:10px;">Image</th>
                                    <th style="text-align:left;padding:10px;">Name</th>
                                    <th style="text-align:left;padding:10px;">Price</th>
                                    <th style="text-align:left;padding:10px;">Created</th>
                                    <th style="text-align:center;padding:10px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($myProducts as $i => $p): ?>
                                <tr style="border-bottom:1px solid #222;">
                                    <td style="padding:10px;">
                                        <?php if(!empty($p['img'])): ?>
                                            <img src="<?= htmlspecialchars($p['img']) ?>" style="width:60px;height:60px;object-fit:cover;border-radius:6px;">
                                        <?php else: ?>
                                            <span>-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:10px;"><?= htmlspecialchars($p['name'] ?? '') ?></td>
                                    <td style="padding:10px;">R<?= number_format($p['price'] ?? 0, 2) ?></td>
                                    <td style="padding:10px;font-size:0.85em;color:#aaa;"><?= htmlspecialchars($p['created_at'] ?? '') ?></td>
                                    <td style="padding:10px;text-align:center;">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="product_index" value="<?= $i ?>">
                                            <button class="btn" type="submit" onclick="return confirm('Delete product?')" style="background:red;padding:5px 10px;font-size:0.85em;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        <!-- REGISTRATION FORM -->
        <?php else: ?>
            <div style="max-width:600px;margin:30px auto;background:#1a1a1a;padding:30px;border-radius:10px;">
                <form method="POST">
                    <input type="hidden" name="action" value="register">
                    <div class="form-row">
                        <label>Store Name</label>
                        <input type="text" name="store_name" required value="<?= htmlspecialchars($currentSeller['store_name'] ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <label>Owner Name</label>
                        <input type="text" name="owner_name" required value="<?= htmlspecialchars($currentSeller['owner_name'] ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <label>Bank Name</label>
                        <input type="text" name="bank_name" required value="<?= htmlspecialchars($currentSeller['bank_name'] ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <label>Account Holder Name</label>
                        <input type="text" name="account_name" required value="<?= htmlspecialchars($currentSeller['account_name'] ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <label>Account Number (6-20 digits)</label>
                        <input type="text" name="account_number" required value="<?= htmlspecialchars($currentSeller['account_number'] ?? '') ?>">
                    </div>

                    <div class="form-actions">
                        <button class="btn" type="submit">Submit Application</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

</div>

<!-- FOOTER -->
<footer>
    <p>© 2026 Pastimes Clothing</p>
</footer>

</body>
</html>
<script>
document.getElementById('imageInput')?.addEventListener('change', function(e){
    const f = this.files && this.files[0];
    const p = document.getElementById('preview');
    if (!f) { p.style.display='none'; p.src=''; return; }
    const reader = new FileReader();
    reader.onload = function(ev){ p.src = ev.target.result; p.style.display = 'block'; };
    reader.readAsDataURL(f);
});
</script>
