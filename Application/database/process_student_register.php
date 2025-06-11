<?php
require_once 'connection.php';

$connection = create_connection();

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
        // Check if email already exists
        $email_escaped = pg_escape_string($connection, $email);
        $check_query = "SELECT student_id FROM Students WHERE email = '$email_escaped'";
        $result = pg_query($connection, $check_query);
        
        if (!$result) {
            $errors[] = "Database error occurred";
            error_log("Student registration check error: " . pg_last_error($connection));
        } elseif (pg_num_rows($result) > 0) {
            $errors[] = "Email already registered";
        }
    }
    
    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Escape all input data
        $name_escaped = pg_escape_string($connection, $name);
        $email_escaped = pg_escape_string($connection, $email);
        $phone_escaped = pg_escape_string($connection, $phone);
        $department_escaped = pg_escape_string($connection, $department);
        $academic_year_escaped = pg_escape_string($connection, $academic_year);
        $password_escaped = pg_escape_string($connection, $hashed_password);
        
        // Insert student record
        $insert_query = "INSERT INTO Students (name, email, phone, department, academic_year, password_hash) 
                        VALUES ('$name_escaped', '$email_escaped', '$phone_escaped', '$department_escaped', '$academic_year_escaped', '$password_escaped')";
        
        $result = pg_query($connection, $insert_query);
        
        if ($result) {
            // Registration successful
            $_SESSION['success'] = "Registration successful! You can now log in.";
            header("Location: ../views/login.html");
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
        header("Location: ../views/student_register.html");
        exit();
    }
    
} else {
    header("Location: ../views/student_register.html");
    exit();
}

// Close the connection
pg_close($connection);
?>