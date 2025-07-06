<?php
require_once 'connection.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
   
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = $_POST['department'];
    $academic_year = $_POST['academic_year'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    if (empty($name) || empty($email) || empty($department) || empty($academic_year) || empty($password)) {
        $errors[] = "All required fields must be filled.";
    }
  
    if (!empty($email) && !preg_match('/.*@strathmore\.edu$/', $email)) {
        $errors[] = "Please use your Strathmore University email address (@strathmore.edu).";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
   
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM students WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "An account with this email already exists.";
        }
        $stmt->close();
    }
  
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
  
        $stmt = $conn->prepare("INSERT INTO students (name, email, phone, department, academic_year, password, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssss", $name, $email, $phone, $department, $academic_year, $hashed_password);
        
        if ($stmt->execute()) {
            $success_message = "Registration successful! You can now log in.";
            $name = $email = $phone = $department = $academic_year = '';
        } else {
            $error_message = "Registration failed. Please try again.";
        }
        $stmt->close();
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="../static/css/index.css">
        <title>Student Registration</title>
        <link rel="icon" type="image/x-icon" href="/static/images/title.png">
    </head>
    <body>
        <div class="container form-container">
            <!-- SVG for the Background flowing with Aurora based colors -->
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

            <form id="student-register-form" class="register-form" action="../database/process_student_register.php" method="post">
                <!-- Heading for our form -->
                <h2 class="form-heading">Student Registration</h2>

                <!-- Full Name Input -->
                <div class="input-group">
                    <div class="input-container">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="input-icon">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <input 
                            type="text" 
                            placeholder="Full Name"
                            name="name"
                            class="form-input"
                            required />
                    </div>
                </div>

                <!-- Email Input -->
                <div class="input-group">
                    <div class="input-container">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="input-icon">
                            <rect width="20" height="16" x="2" y="4" rx="2"/>
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                        </svg>
                        <input 
                            type="email" 
                            placeholder="Student Email (@strathmore.edu)"
                            name="email" 
                            class="form-input"
                            pattern=".*@strathmore\.edu$"
                            title="Please use your Strathmore University email address"
                            required />
                    </div>
                </div>

                <!-- Phone Input -->
                <div class="input-group">
                    <div class="input-container">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="input-icon">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        <input 
                            type="tel" 
                            placeholder="Phone Number"
                            name="phone" 
                            class="form-input" />
                    </div>
                </div>

                <!-- Department Dropdown -->
                <div class="input-group">
                    <div class="input-container">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="input-icon">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                        </svg>
                        <select name="department" class="form-input" required>
                            <option value="">Select Department</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Electrical Engineering">Electrical Engineering</option>
                            <option value="Mechanical Engineering">Mechanical Engineering</option>
                            <option value="Civil Engineering">Civil Engineering</option>
                            <option value="Business Administration">Business Administration</option>
                            <option value="Finance">Finance</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Accounting">Accounting</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Economics">Economics</option>
                            <option value="Law">Law</option>
                            <option value="Medicine">Medicine</option>
                            <option value="Nursing">Nursing</option>
                            <option value="Education">Education</option>
                            <option value="Psychology">Psychology</option>
                        </select>
                    </div>
                </div>

                <!-- Academic Year Dropdown -->
                <div class="input-group">
                    <div class="input-container">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="input-icon">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <select name="academic_year" class="form-input" required>
                            <option value="">Select Academic Year</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                            <option value="5th Year">5th Year</option>
                            <option value="Graduate">Graduate</option>
                            <option value="Masters">Masters</option>
                            <option value="PhD">PhD</option>
                        </select>
                    </div>
                </div>

                <!-- Password Input -->
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

                <!-- Confirm Password Input -->
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

                <!-- Register button -->
                <div class="button-container">
                    <button type="submit" class="submit-btn">Register as Student</button>
                </div>

                <!-- Navigation Links -->
                <div class="form-switch-container">
                    <a class="form-switch-link" href="login.html">Already have an account? Log In</a>
                </div>

                <div class="form-switch-container" style="margin-top: calc(var(--spacing) * 2);">
                    <a class="form-switch-link" href="employer_register.html">Register as Employer instead</a>
                </div>

                <!-- Error message -->
                <div id="password-error" class="error-message" hidden>
                    🔒 Passwords do not match. Please try again.
                </div>

                <div id="email-error" class="error-message" hidden>
                    📧 Please use your Strathmore University email address (@strathmore.edu).
                </div>
            </form>
        </div>

        <footer>
            Intern Connect  &copy; 2025
        </footer>

        <!-- JavaScript for form validation -->
        <script>
            document.getElementById('student-register-form').addEventListener('submit', function(e) {
                const password = document.querySelector('input[name="password"]').value;
                const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
                const email = document.querySelector('input[name="email"]').value;
                
                const passwordError = document.getElementById('password-error');
                const emailError = document.getElementById('email-error');
                
                let hasError = false;

                // Reset error displays
                passwordError.style.display = "none";
                emailError.style.display = "none";

                // Password validation
                if (password !== confirmPassword) {
                    e.preventDefault();
                    passwordError.style.display = "block";
                    hasError = true;
                    
                    setTimeout(() => {
                        passwordError.style.display = "none";
                    }, 5000);
                }

                // Email validation for Strathmore domain
                if (!email.endsWith('@strathmore.edu')) {
                    e.preventDefault();
                    emailError.style.display = "block";
                    hasError = true;
                    
                    setTimeout(() => {
                        emailError.style.display = "none";
                    }, 5000);
                }

                // If no errors, allow form submission
                if (!hasError) {
                    passwordError.style.display = "none";
                    emailError.style.display = "none";
                }
            });

            // Real-time email validation
            document.querySelector('input[name="email"]').addEventListener('input', function(e) {
                const email = e.target.value;
                const emailError = document.getElementById('email-error');
                
                if (email && !email.endsWith('@strathmore.edu')) {
                    emailError.style.display = "block";
                } else {
                    emailError.style.display = "none";
                }
            });

            // Style select dropdowns to match other inputs
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

        <style>
            /* Additional styles for select dropdowns */
            select.form-input {
                cursor: pointer;
            }

            select.form-input option {
                background-color: var(--color-blue-900);
                color: var(--color-white);
                padding: calc(var(--spacing) * 2);
            }

            .success-message {
                background-color: rgba(34, 197, 94, 0.1);
                color: var(--color-cyan-300);
                border: 1px solid var(--color-cyan-400);
                font-size: var(--text-sm);
                border-radius: var(--radius-lg);
                padding: calc(var(--spacing) * 3) calc(var(--spacing) * 4);
                margin-top: calc(var(--spacing) * 3);
                backdrop-filter: blur(var(--blur-md));
                display: none;
            }
        </style>
    </body>
</html>