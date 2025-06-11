<?php
require_once 'connection.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    
    $company_name = trim($_POST['company_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $industry = $_POST['industry'];
    $address = trim($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
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
        $errors[] = "Industry is required";
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
            $stmt = $pdo->prepare("SELECT id FROM employers WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Email already registered";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error occurred";
            error_log("Employer registration check error: " . $e->getMessage());
        }
    }
    
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO employers (company_name, email, phone, industry, address, password, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
            $stmt->execute([$company_name, $email, $phone, $industry, $address, $hashed_password]);
            
            $_SESSION['success'] = "Registration successful! You can now log in.";
            header("Location: ../views/login.html");
            exit();
            
        } catch (PDOException $e) {
            $errors[] = "Registration failed. Please try again.";
            error_log("Employer registration error: " . $e->getMessage());
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['register_errors'] = $errors;
        $_SESSION['old_data'] = [
            'company_name' => $company_name,
            'email' => $email,
            'phone' => $phone,
            'industry' => $industry,
            'address' => $address
        ];
        header("Location: ../views/employer_register.html");
        exit();
    }
    
} else {
    header("Location: ../views/employer_register.html");
    exit();
}
?>