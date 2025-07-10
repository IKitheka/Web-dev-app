<?php
require_once 'connection.php';

$connection = create_connection();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
        $check_query = 'SELECT student_id FROM "Students" WHERE email = $1';
        $result = pg_query_params($connection, $check_query, array($email));
        if (!$result) {
            $errors[] = "Database error occurred";
            error_log("Student registration check error: " . pg_last_error($connection));
        } elseif (pg_num_rows($result) > 0) {
            $errors[] = "Email already registered";
        }
    }
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $insert_query = 'INSERT INTO "Students" (name, email, phone, department, academic_year, password_hash) VALUES ($1, $2, $3, $4, $5, $6)';
        $result = pg_query_params($connection, $insert_query, array($name, $email, $phone, $department, $academic_year, $hashed_password));
        if ($result) {
            $_SESSION['success'] = "Registration successful! You can now log in.";
            header("Location: ../Authentication/login.php");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
            error_log("Student registration error: " . pg_last_error($connection));
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
        header("Location: ../Authentication/student_register.php");
        exit();
    }
} else {
    header("Location: ../Authentication/student_register.php");
    exit();
}
pg_close($connection);
?>