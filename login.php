<?php
/**
 * FuelDeskPro - Login Page
 * 
 * @package FuelDeskPro
 */

session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    header("Location: dashboard.php");
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email    = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        // Query user by email
        $sql = "SELECT * FROM sys_user WHERE Email = ? AND IsActive = 1 AND IsDeleted = 0";
        $user = $objQuery->index($sql, [$email]);

        if (!empty($user)) {
            $user = $user[0];
            // Check password (plain text for now - should use password_hash in production)
            if ($password === $user->PasswordHash) {
                // Set session
                $_SESSION['user_id']    = $user->UserID;
                $_SESSION['user_name']  = $user->FullName;
                $_SESSION['user_email'] = $user->Email;
                $_SESSION['last_activity'] = time();

                // Update last login
                $upd = "UPDATE sys_user SET LastLoginAt = NOW() WHERE UserID = ?";
                $objQuery->inUpDel($upd, [$user->UserID]);

                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid email or password!";
            }
        } else {
            $error = "Invalid email or password!";
        }
    } else {
        $error = "Please enter email and password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FuelDeskPro - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            padding: 40px;
            width: 420px;
            max-width: 90%;
        }
        .login-card .login-icon {
            font-size: 48px;
            color: #0d6efd;
            margin-bottom: 20px;
        }
        .login-card h3 {
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        .login-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .login-card .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
        }
        .login-card .btn-primary {
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
        }
        .login-card .alert {
            border-radius: 8px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="login-card text-center">
        <div class="login-icon">
            <i class="fas fa-gas-pump"></i>
        </div>
        <h3>FuelDeskPro</h3>
        <p class="text-muted">Fuel Station Management System</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['expired'])): ?>
            <div class="alert alert-warning">Session expired. Please login again.</div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3 text-start">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="mb-3 text-start">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>
        </form>

        <div class="mt-4 text-muted small">
            <p class="mb-0">Shangu LPG Filling Station</p>
            <p>Amilaish, Satkania, Chattogram.</p>
        </div>
    </div>
</body>
</html>