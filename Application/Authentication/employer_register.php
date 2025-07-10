<?php
session_start();
require_once '../database/connection.php';
function sendVerificationEmail($email, $token) {
    $verification_url = "http://" . $_SERVER['HTTP_HOST'] . "/verify_employer.php?token=" . $token;
    $subject = "Verify Your Employer Account - Intern Connect";
    $message = "
    <html>
    <head>
        <title>Verify Your Account</title>
    </head>
    <body>
        <h2>Welcome to Intern Connect!</h2>
        <p>Thank you for registering as an employer. Please click the link below to verify your account:</p>
        <p><a href='{$verification_url}'>Verify Account</a></p>
        <p>If you cannot click the link, copy and paste this URL into your browser:</p>
        <p>{$verification_url}</p>
        <p>This link will expire in 24 hours.</p>
    </body>
    </html>
    ";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@internconnect.com" . "\r\n";
    error_log("Verification email would be sent to: " . $email . " with token: " . $token);
    return true;
}
$error_message = '';
$success_message = '';
if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $errors = [];
    if (empty($company_name)) {
        $errors[] = "Company name is required";
    }
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    if (empty($industry)) {
        $errors[] = "Industry selection is required";
    }
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    if (empty($location)) {
        $errors[] = "Location is required";
    }
    if (empty($errors)) {
        $connection = create_connection();
        $check_query = "SELECT employer_id FROM Employers WHERE email = $1";
        $result = pg_query_params($connection, $check_query, array($email));
        if (!$result) {
            $error_message = "Database error occurred";
            error_log("Employer registration check error: " . pg_last_error($connection));
        } elseif (pg_num_rows($result) > 0) {
            $error_message = "An account with this email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO Employers (company_name, email, phone, industry, location, password_hash) VALUES ($1, $2, $3, $4, $5, $6)";
            $result = pg_query_params($connection, $insert_query, array($company_name, $email, $phone, $industry, $location, $hashed_password));
            if ($result) {
                $_SESSION['success'] = "Registration successful! You can now log in.";
                header("Location: ../Authentication/login.php");
                exit();
            } else {
                $error_message = "Registration failed. Please try again.";
                error_log("Employer registration error: " . pg_last_error($connection));
            }
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}
$industries = [
    'Technology',
    'Healthcare',
    'Finance',
    'Education',
    'Manufacturing',
    'Retail',
    'Construction',
    'Agriculture',
    'Transportation',
    'Energy',
    'Real Estate',
    'Media & Entertainment',
    'Telecommunications',
    'Government',
    'Non-Profit',
    'Consulting',
    'Legal',
    'Tourism & Hospitality',
    'Food & Beverage',
    'Other'
];
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="../static/css/index.css">
        <title>Employer Registration</title>
        <link rel="icon" type="image/x-icon" href="/static/images/title.png">
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
            <form id="employer-register-form" class="register-form" action="../database/process_employer_register.php" method="post">
                <h2 class="form-heading">Employer Registration</h2>
                <div class="input-group">
                    <div class="input-container">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="input-icon">
                            <path d="M3 21h18"/>
                            <path d="M5 21V7l8-4v18"/>
                            <path d="M19 21V11l-6-4"/>
                            <path d="M9 9v.01"/>
                            <path d="M9 12v.01"/>
                            <path d="M9 15v.01"/>
                            <path d="M9 18v.01"/>
                        </svg>
                        <input 
                            type="text" 
                            placeholder="Company Name"
                            name="company_name"
                            class="form-input"
                            required />
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
                            placeholder="Company Email Address"
                            name="email" 
                            class="form-input"
                            required />
                    </div>
                </div>
                <div class="input-group">
                    <div class="input-container">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="input-icon">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        <input 
                            type="tel" 
                            placeholder="Company Phone Number"
                            name="phone" 
                            class="form-input" />
                    </div>
                </div>
                <div class="input-group">
                    <div class="input-container">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="input-icon">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                            <path d="m2 17 10 5 10-5"/>
                            <path d="m2 12 10 5 10-5"/>
                        </svg>
                        <select name="industry" class="form-input" required>
                            <option value="">Select Industry</option>
                            <option value="Technology">Technology</option>
                            <option value="Healthcare">Healthcare</option>
                            <option value="Finance">Finance</option>
                            <option value="Education">Education</option>
                            <option value="Manufacturing">Manufacturing</option>
                            <option value="Retail">Retail</option>
                            <option value="Construction">Construction</option>
                            <option value="Agriculture">Agriculture</option>
                            <option value="Transportation">Transportation</option>
                            <option value="Energy">Energy</option>
                            <option value="Real Estate">Real Estate</option>
                            <option value="Media & Entertainment">Media & Entertainment</option>
                            <option value="Telecommunications">Telecommunications</option>
                            <option value="Government">Government</option>
                            <option value="Non-Profit">Non-Profit</option>
                            <option value="Consulting">Consulting</option>
                            <option value="Legal">Legal</option>
                            <option value="Tourism & Hospitality">Tourism & Hospitality</option>
                            <option value="Food & Beverage">Food & Beverage</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="input-group">
                    <div class="input-container">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="input-icon">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <textarea 
                            placeholder="Company Location"
                            name="location" 
                            class="form-textarea"
                            rows="3"
                            style="padding-left: calc(var(--spacing) * 10); min-height: calc(var(--spacing) * 16);"></textarea>
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
                            minlength="8"
                            required />
                    </div>
                </div>
                <div class="input-group">
                    <div class="input-container">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="input-icon">
                            <path d="m14.5 9.5 1 1"/>
                            <path d="m15.5 8.5-4 4"/>
                            <path d="M3 12a9 9 0 1 0 9-9 9.74 9.74 0 0 0-6.74 2.74L3 8"/>
                            <path d="M3 3v5h5"/>
                            <circle cx="10" cy="14" r="2"/>
                        </svg>
                        <input 
                            type="password" 
                            placeholder="Confirm Password"
                            name="confirm_password" 
                            class="form-input"
                            required />
                    </div>
                </div>
                <div class="button-container">
                    <button type="submit" class="submit-btn">Register as Employer</button>
                </div>
                <div class="form-switch-container">
                    <a class="form-switch-link" href="login.php">Already have an account? Log In</a>
                </div>
                <div class="form-switch-container" style="margin-top: calc(var(--spacing) * 2);">
                    <a class="form-switch-link" href="student_register.php">Register as Student instead</a>
                </div>
                <div id="password-error" class="error-message" hidden>
                    🔒 Passwords do not match. Please try again.
                </div>
            </form>
        </div>
        <footer>
            Intern Connect  &copy; 2025
        </footer>
        <script>
            document.getElementById('employer-register-form').addEventListener('submit', function(e) {
                const password = document.querySelector('input[name="password"]').value;
                const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
                const passwordError = document.getElementById('password-error');
                let hasError = false;
                passwordError.style.display = "none";
                if (password !== confirmPassword) {
                    e.preventDefault();
                    passwordError.style.display = "block";
                    hasError = true;
                    setTimeout(() => {
                        passwordError.style.display = "none";
                    }, 5000);
                }
                if (!hasError) {
                    passwordError.style.display = "none";
                }
            });
            document.addEventListener('DOMContentLoaded', function() {
                const selects = document.querySelectorAll('select.form-input');
                selects.forEach(select => {
                    select.style.appearance = 'none';
                    select.style.backgroundImage = `url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e")`;
                    select.style.backgroundRepeat = 'no-repeat';
                    select.style.backgroundPosition = 'right 12px center';
                    select.style.backgroundSize = '16px';
                    select.style.paddingRight = 'calc(var(--spacing) * 10)';
                });
            });
        </script>
    </body>
</html>