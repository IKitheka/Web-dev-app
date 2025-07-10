<?php
session_start();
require_once '../database/connection.php';
require_once '../database/auth_helpers.php';
$error_message = '';
$success_message = '';
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    if (validate_user_session()) {
        switch ($_SESSION['user_type']) {
            case 'student':
                header("Location: ../Dashboards/student_dashboard.php");
                break;
            case 'employer':
                header("Location: ../Dashboards/employer_dashboard.php");
                break;
            case 'admin':
                header("Location: ../Dashboards/admin_dashboard.php");
                break;
        }
        exit();
    } else {
        session_destroy();
        session_start();
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_type = trim($_POST['user_type'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    if (empty($user_type) || empty($email) || empty($password)) {
        $error_message = "⚠️ Please fill in all required fields.";
    } 
    elseif ($user_type === 'student' && !str_ends_with($email, '@strathmore.edu')) {
        $error_message = "🎓 Students must use their Strathmore University email address (@strathmore.edu).";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "📧 Please enter a valid email address.";
    }
    else {
        try {
            $conn = create_connection();
            $table = '';
            $id_column = '';
            $name_column = '';
            switch ($user_type) {
                case 'student':
                    $table = 'Students';
                    $id_column = 'student_id';
                    $name_column = 'name';
                    break;
                case 'employer':
                    $table = 'Employers';
                    $id_column = 'employer_id';
                    $name_column = 'company_name';
                    break;
                case 'admin':
                    $table = 'Administrators';
                    $id_column = 'admin_id';
                    $name_column = 'full_name';
                    break;
                default:
                    $error_message = "Invalid account type selected.";
                    break;
            }
            if (!$error_message) {
                $sql = "SELECT $id_column as user_id, email, password_hash, $name_column as name";
                if ($user_type !== 'admin') {
                    $sql .= ", status, deleted_at, disabled_at, disabled_reason";
                }
                $sql .= " FROM \"$table\" WHERE email = $1";
                $result = pg_query_params($conn, $sql, [$email]);
                if ($result && $user = pg_fetch_assoc($result)) {
                    if (password_verify($password, $user['password_hash'])) {
                        if ($user_type !== 'admin') {
                            if ($user['deleted_at'] !== null) {
                                $error_message = "🚫 Your account has been removed. Please contact support for assistance.";
                            }
                            elseif ($user['status'] !== 'active') {
                                $reason = $user['disabled_reason'] ? ': ' . $user['disabled_reason'] : '';
                                if ($user['status'] === 'disabled') {
                                    $error_message = "🚫 Your account has been disabled" . $reason . ". Please contact support for assistance.";
                                } elseif ($user['status'] === 'suspended') {
                                    $error_message = "⏸️ Your account has been suspended" . $reason . ". Please contact support for assistance.";
                                } else {
                                    $error_message = "🚫 Your account is not active. Please contact support for assistance.";
                                }
                            }
                        }
                        if (!$error_message) {
                            $_SESSION['user_id'] = $user['user_id'];
                            $_SESSION['user_type'] = $user_type;
                            $_SESSION['user_email'] = $user['email'];
                            $_SESSION['user_name'] = $user['name'];
                            $_SESSION['login_time'] = time();
                            if ($remember_me) {
                                ini_set('session.gc_maxlifetime', 30 * 24 * 60 * 60);
                                session_set_cookie_params(30 * 24 * 60 * 60);
                            }
                            switch ($user_type) {
                                case 'student':
                                    header("Location: ../Dashboards/student_dashboard.php");
                                    break;
                                case 'employer':
                                    header("Location: ../Dashboards/employer_dashboard.php");
                                    break;
                                case 'admin':
                                    header("Location: ../Dashboards/admin_dashboard.php");
                                    break;
                            }
                            exit();
                        }
                    } else {
                        $error_message = "❌ Invalid credentials. Please check your email, password, and account type.";
                    }
                } else {
                    $error_message = "❌ Invalid credentials. Please check your email, password, and account type.";
                }
            }
            pg_close($conn);
        } catch (Exception $e) {
            $error_message = "🔧 Login failed. Please try again later.";
            error_log("Login error: " . $e->getMessage());
        }
    }
}
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'session_expired':
            $error_message = "⏰ Your session has expired. Please log in again.";
            break;
        case 'access_denied':
            $error_message = "🚫 Access denied. Please log in with appropriate credentials.";
            break;
        default:
            $error_message = "❌ An error occurred. Please try again.";
    }
}
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'registered':
            $success_message = "✅ Registration successful! Please sign in with your credentials.";
            break;
        case 'logout':
            $success_message = "👋 You have been logged out successfully.";
            break;
        default:
            $success_message = "✅ Action completed successfully.";
    }
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="../static/css/index.css">
        <title>Login - Intern Connect</title>
        <link rel="icon" type="image/x-icon" href="../static/images/title.png">
    </head>
    <body>
        <div class="container form-container">
            <svg class="bg-svg" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
                <path fill="url(#aurora-gradient)" d="M0,128L60,138.7C120,149,240,171,360,154.7C480,139,600,85,720,96C840,107,960,181,1080,197.3C1200,213,1320,171,1380,149.3L1440,128L1440,0L1380,0C1320,0,1200,0,1080,0C960,0,840,0,720,0C600,0,480,0,360,0C240,0,120,0,60,0L0,0Z"></path>
                <defs>
                    <linearGradient id="aurora-gradient" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#4f46e5"/>
                        <stop offset="50%" stop-color="#06b6d4"/>
                        <stop offset="100%" stop-color="#8b5cf6"/>
                    </linearGradient>
                </defs>
            </svg>
            <form id="login-form" class="register-form" method="post">
                <h2 class="form-heading">Sign In</h2>
                <p style="text-align: center; color: rgba(255, 255, 255, 0.8); margin-bottom: calc(var(--spacing) * 6); font-size: 1.1rem;">
                    Welcome back to Intern Connect
                </p>
                <?php if ($error_message): ?>
                <div class="error-message" style="display: block; margin-bottom: calc(var(--spacing) * 4);">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                <div class="success-message" style="display: block; margin-bottom: calc(var(--spacing) * 4); background: rgba(34, 197, 94, 0.2); color: #22c55e; padding: calc(var(--spacing) * 3); border-radius: calc(var(--border-radius) * 2); border: 1px solid rgba(34, 197, 94, 0.3);">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
                <?php endif; ?>
                <div class="input-group">
                    <div class="input-container">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="input-icon">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="m22 21-3-3"/>
                            <circle cx="17" cy="17" r="3"/>
                        </svg>
                        <select name="user_type" class="form-input" required>
                            <option value="">Select Account Type</option>
                            <option value="student" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'student') ? 'selected' : ''; ?>>Student</option>
                            <option value="employer" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'employer') ? 'selected' : ''; ?>>Employer</option>
                            <option value="admin" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                    </div>
                </div>
                <div class="input-group">
                    <div class="input-container">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="input-icon">
                            <rect width="20" height="16" x="2" y="4" rx="2"/>
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                        </svg>
                        <input 
                            type="email" 
                            placeholder="Email Address"
                            name="email" 
                            class="form-input"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required />
                    </div>
                </div>
                <div class="input-group">
                    <div class="input-container">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="input-icon">
                            <path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/>
                            <circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/>
                        </svg>
                        <input 
                            type="password" 
                            placeholder="Password"
                            name="password" 
                            class="form-input"
                            required />
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: calc(var(--spacing) * 4);">
                    <label style="display: flex; align-items: center; color: rgba(255, 255, 255, 0.9); font-size: 0.9rem; cursor: pointer;">
                        <input 
                            type="checkbox" 
                            name="remember_me" 
                            style="margin-right: calc(var(--spacing) * 2); accent-color: var(--color-cyan-500);">
                        Remember me
                    </label>
                    <a href="forgot_password.php" class="form-switch-link" style="font-size: 0.9rem; margin: 0;">
                        Forgot Password?
                    </a>
                </div>
                <div class="button-container">
                    <button type="submit" class="submit-btn">Sign In</button>
                </div>
                <div style="text-align: center; margin: calc(var(--spacing) * 6) 0 calc(var(--spacing) * 4) 0; position: relative;">
                    <span style="background: linear-gradient(to bottom right, var(--color-blue-900), var(--color-indigo-800), var(--color-purple-900)); padding: 0 1rem; color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;">
                        Don't have an account?
                    </span>
                    <div style="position: absolute; top: 50%; left: 0; right: 0; height: 1px; background: rgba(255, 255, 255, 0.2); z-index: -1;"></div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: calc(var(--spacing) * 3); margin-top: calc(var(--spacing) * 4);">
                    <a href="student_register.php" class="btn" style="text-align: center; text-decoration: none; margin: 0; padding: calc(var(--spacing) * 3); background-color: rgba(59, 130, 246, 0.3); border: 1px solid rgba(59, 130, 246, 0.5);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        Student Sign Up
                    </a>
                    <a href="employer_register.php" class="btn" style="text-align: center; text-decoration: none; margin: 0; padding: calc(var(--spacing) * 3); background-color: rgba(168, 85, 247, 0.3); border: 1px solid rgba(168, 85, 247, 0.5);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 21h18"/>
                            <path d="M5 21V7l8-4v18"/>
                            <path d="M19 21V11l-6-4"/>
                        </svg>
                        Employer Sign Up
                    </a>
                </div>
            </form>
        </div>
        <footer>
            Intern Connect &copy; 2025 | All Rights Reserved
        </footer>
        <script>
            document.getElementById('login-form').addEventListener('submit', function(e) {
                const userType = document.querySelector('select[name="user_type"]').value;
                const email = document.querySelector('input[name="email"]').value;
                const password = document.querySelector('input[name="password"]').value;
                if (!userType || !email || !password) {
                    e.preventDefault();
                    alert('⚠️ Please fill in all required fields.');
                    return;
                }
                if (userType === 'student' && !email.endsWith('@strathmore.edu')) {
                    e.preventDefault();
                    alert('🎓 Students must use their Strathmore University email address (@strathmore.edu).');
                    return;
                }
                const submitBtn = this.querySelector('.submit-btn');
                submitBtn.innerHTML = `
                    <svg style="animation: spin 1s linear infinite; display: inline-block; margin-right: 0.5rem;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12a9 9 0 11-6.219-8.56"/>
                    </svg>
                    Signing In...
                `;
                submitBtn.disabled = true;
            });
            document.querySelector('select[name="user_type"]').addEventListener('change', function(e) {
                const userType = e.target.value;
                const emailInput = document.querySelector('input[name="email"]');
                switch(userType) {
                    case 'student':
                        emailInput.placeholder = "Student Email (@strathmore.edu)";
                        break;
                    case 'employer':
                        emailInput.placeholder = "Company Email Address";
                        break;
                    case 'admin':
                        emailInput.placeholder = "Administrator Email";
                        break;
                    default:
                        emailInput.placeholder = "Email Address";
                }
            });
            document.addEventListener('DOMContentLoaded', function() {
                const selects = document.querySelectorAll('select.form-input');
                selects.forEach(select => {
                    select.style.appearance = 'none';
                    select.style.backgroundImage = `url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e")`;
                    select.style.backgroundRepeat = 'no-repeat';
                    select.style.backgroundPosition = 'right 12px center';
                    select.style.backgroundSize = '16px';
                    select.style.paddingRight = 'calc(var(--spacing) * 10)';
                });
            });
        </script>
        <style>
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            .register-form {
                max-width: 32rem;
            }
            input[type="checkbox"] {
                width: 1rem;
                height: 1rem;
                border-radius: 0.25rem;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            }
            .btn {
                transition: all 0.3s ease;
                font-weight: 600;
                border-radius: 0.75rem;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            @media (max-width: 640px) {
                .register-form {
                    max-width: 95%;
                }
                div[style*="grid-template-columns"] {
                    grid-template-columns: 1fr !important;
                    gap: calc(var(--spacing) * 2) !important;
                }
            }
            .success-message {
                text-align: center;
                font-weight: 500;
            }
        </style>
    </body>
</html>
