<?php
/**
 * FuelDeskPro - Premium Modern Login Page
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

// Fetch Company Profile dynamically from Database
$company = getCompanyInfo();
$compName = $company ? (!empty($company->CompanyName) ? $company->CompanyName : 'Sanghu LPG Filling Station') : 'Sanghu LPG Filling Station';
$compAddress = $company ? (!empty($company->Address) ? $company->Address : 'Kaliaish, Satkania, Chattogram.') : 'Kaliaish, Satkania, Chattogram.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FuelDeskPro - Login</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --accent-glow: rgba(37, 99, 235, 0.35);
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: radial-gradient(circle at 15% 15%, #1e293b 0%, #0f172a 40%, #020617 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Ambient Glowing Background Elements */
        .ambient-orb-1 {
            position: absolute;
            width: 380px;
            height: 380px;
            top: -100px;
            left: -100px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.4) 0%, rgba(37, 99, 235, 0) 70%);
            filter: blur(40px);
            z-index: 0;
            pointer-events: none;
        }

        .ambient-orb-2 {
            position: absolute;
            width: 420px;
            height: 420px;
            bottom: -120px;
            right: -120px;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.35) 0%, rgba(14, 165, 233, 0) 70%);
            filter: blur(50px);
            z-index: 0;
            pointer-events: none;
        }

        .login-card {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.2);
            padding: 40px 36px;
            width: 450px;
            max-width: 100%;
            animation: cardEntrance 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes cardEntrance {
            from {
                opacity: 0;
                transform: translateY(24px) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Top Header Block (Logo + Company Name + Address) */
        .top-header-block {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-bottom: 18px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 22px;
            text-align: left;
        }

        .brand-badge-top {
            width: 58px;
            height: 58px;
            min-width: 58px;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            box-shadow: 0 8px 20px var(--accent-glow);
            transition: transform 0.3s ease;
        }

        .brand-badge-top:hover {
            transform: translateY(-2px) rotate(4deg);
        }

        .company-title-top {
            font-size: 19px;
            font-weight: 800;
            letter-spacing: -0.3px;
            color: #0f172a;
            line-height: 1.25;
            margin: 0;
        }

        .company-address-top {
            font-size: 12.5px;
            color: #64748b;
            font-weight: 500;
            margin-top: 3px;
            line-height: 1.35;
        }

        .system-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(37, 99, 235, 0.08);
            color: var(--primary-color);
            font-size: 12.5px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 20px;
            margin-bottom: 22px;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
        }

        .input-icon-left {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
            transition: color 0.2s ease;
            z-index: 5;
        }

        .form-control-custom {
            width: 100%;
            padding: 13px 16px 13px 44px;
            font-size: 14px;
            font-weight: 500;
            color: #1e293b;
            background-color: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.25s ease;
        }

        .form-control-custom:focus {
            background-color: #ffffff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px var(--accent-glow);
            outline: none;
        }

        .input-group-custom:focus-within .input-icon-left {
            color: var(--primary-color);
        }

        .toggle-password-btn {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 16px;
            cursor: pointer;
            padding: 4px;
            z-index: 5;
            transition: color 0.2s ease;
        }

        .toggle-password-btn:hover {
            color: #475569;
        }

        .btn-submit {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            font-weight: 700;
            font-size: 15px;
            border: none;
            border-radius: 12px;
            padding: 14px;
            width: 100%;
            margin-top: 8px;
            box-shadow: 0 8px 20px var(--accent-glow);
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.45);
            color: #ffffff;
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .alert-custom {
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 500;
            padding: 12px 16px;
            margin-bottom: 20px;
            border: none;
        }
    </style>
</head>
<body>
    <!-- Ambient Background Effects -->
    <div class="ambient-orb-1"></div>
    <div class="ambient-orb-2"></div>

    <div class="login-card text-center">
        <!-- Top Header: Logo + Company Name + Address -->
        <div class="top-header-block">
            <div class="brand-badge-top">
                <i class="fas fa-gas-pump"></i>
            </div>
            <div>
                <h3 class="company-title-top"><?php echo htmlspecialchars($compName); ?></h3>
                <div class="company-address-top">
                    <i class="fas fa-location-dot text-danger me-1"></i><?php echo htmlspecialchars($compAddress); ?>
                </div>
            </div>
        </div>

        <!-- System Title Badge -->
        <div class="text-center">
            <div class="system-badge">
                <i class="fas fa-layer-group"></i>
                <span>FuelDeskPro Management System</span>
            </div>
        </div>

        <!-- Error / Expired Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-custom d-flex align-items-center gap-2 text-start">
                <i class="fas fa-exclamation-circle text-danger fs-5"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['expired'])): ?>
            <div class="alert alert-warning alert-custom d-flex align-items-center gap-2 text-start">
                <i class="fas fa-clock text-warning fs-5"></i>
                <div>Session expired. Please login again.</div>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="">
            <div class="text-start mb-3">
                <label class="form-label fw-semibold small text-secondary ms-1">Email Address</label>
                <div class="input-group-custom">
                    <i class="fas fa-envelope input-icon-left"></i>
                    <input type="email" name="email" class="form-control-custom" placeholder="name@company.com" required autocomplete="email" autofocus>
                </div>
            </div>

            <div class="text-start mb-4">
                <label class="form-label fw-semibold small text-secondary ms-1">Password</label>
                <div class="input-group-custom">
                    <i class="fas fa-lock input-icon-left"></i>
                    <input type="password" name="password" id="passwordInput" class="form-control-custom" placeholder="••••••••" required>
                    <button type="button" class="toggle-password-btn" id="togglePasswordBtn" title="Show/Hide Password">
                        <i class="fas fa-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="login" class="btn-submit">
                <i class="fas fa-right-to-bracket"></i> Login to Dashboard
            </button>
        </form>
    </div>

    <!-- Toggle Password Visibility Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('togglePasswordBtn');
            const passwordInput = document.getElementById('passwordInput');
            const toggleIcon = document.getElementById('togglePasswordIcon');

            if (toggleBtn && passwordInput && toggleIcon) {
                toggleBtn.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    if (type === 'text') {
                        toggleIcon.classList.remove('fa-eye');
                        toggleIcon.classList.add('fa-eye-slash');
                    } else {
                        toggleIcon.classList.remove('fa-eye-slash');
                        toggleIcon.classList.add('fa-eye');
                    }
                });
            }
        });
    </script>
</body>
</html>