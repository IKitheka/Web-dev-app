<?php
require_once 'connection.php';

$connection = create_connection();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $company_name = trim($_POST['company_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $industry = $_POST['industry'];
    $location = trim($_POST['location']);
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
    if (empty($location)) {
        $errors[] = "Location is required";
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
        $check_query = 'SELECT employer_id FROM "Employers" WHERE email = $1';
        $result = pg_query_params($connection, $check_query, array($email));
        if (!$result) {
            $errors[] = "Database error occurred";
            error_log("Employer registration check error: " . pg_last_error($connection));
        } elseif (pg_num_rows($result) > 0) {
            $errors[] = "Email already registered";
        }
    }
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $insert_query = 'INSERT INTO "Employers" (company_name, email, phone, industry, location, password_hash) VALUES ($1, $2, $3, $4, $5, $6)';
        $result = pg_query_params($connection, $insert_query, array($company_name, $email, $phone, $industry, $location, $hashed_password));
        if ($result) {
            $_SESSION['success'] = "Registration successful! You can now log in.";
            header("Location: ../Authentication/login.php");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
            error_log("Employer registration error: " . pg_last_error($connection));
        }
    }
    if (!empty($errors)) {
        $_SESSION['register_errors'] = $errors;
        $_SESSION['old_data'] = [
            'company_name' => $company_name,
            'email' => $email,
            'phone' => $phone,
            'industry' => $industry,
            'location' => $location
        ];
        header("Location: ../Authentication/employer_register.php");
        exit();
    }
} else {
    header("Location: ../Authentication/employer_register.php");
    exit();
}
pg_close($connection);
?>