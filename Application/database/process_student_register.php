<?php
require_once 'connection.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = $_POST['department'];
    $academic_year = $_POST['academic_year'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } elseif (!preg_match('/@strathmore\.edu$/', $email)) {
        $errors[] = "Please use your Strathmore University email address";
    }
    
    if (empty($department)) {
        $errors[] = "Department is required";
    }
    
    if (empty($academic_year)) {
        $errors[] = "Academic year is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Email already registered";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error occurred";
            error_log("Student registration check error: " . $e->getMessage());
        }
    }
    
    if (empty($errors)) {
        try {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert student record
            $stmt = $pdo->prepare("INSERT INTO students (name, email, phone, department, academic_year, password, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
            $stmt->execute([$name, $email, $phone, $department, $academic_year, $hashed_password]);
            
            // Registration successful
            $_SESSION['success'] = "Registration successful! You can now log in.";
            header("Location: ../views/login.html");
            exit();
            
        } catch (PDOException $e) {
            $errors[] = "Registration failed. Please try again.";
            error_log("Student registration error: " . $e->getMessage());
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['register_errors'] = $errors;
        $_SESSION['old_data'] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'department' => $department,
            'academic_year' => $academic_year
        ];
        header("Location: ../views/student_register.html");
        exit();
    }
    
} else {
    header("Location: ../views/student_register.html");
    exit();
}
?>