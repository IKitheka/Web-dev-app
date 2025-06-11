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
        // Check if email already exists
        $email_escaped = pg_escape_string($connection, $email);
        $check_query = "SELECT employer_id FROM Employers WHERE email = '$email_escaped'";
        $result = pg_query($connection, $check_query);
        
        if (!$result) {
            $errors[] = "Database error occurred";
            error_log("Employer registration check error: " . pg_last_error($connection));
        } elseif (pg_num_rows($result) > 0) {
            $errors[] = "Email already registered";
        }
    }
    
    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Escape all input data
        $company_name_escaped = pg_escape_string($connection, $company_name);
        $email_escaped = pg_escape_string($connection, $email);
        $phone_escaped = pg_escape_string($connection, $phone);
        $industry_escaped = pg_escape_string($connection, $industry);
        $address_escaped = pg_escape_string($connection, $address);
        $password_escaped = pg_escape_string($connection, $hashed_password);
        
        // Insert employer record
        $insert_query = "INSERT INTO Employers (company_name, email, phone, industry, address, password_hash) 
                        VALUES ('$company_name_escaped', '$email_escaped', '$phone_escaped', '$industry_escaped', '$address_escaped', '$password_escaped')";
        
        $result = pg_query($connection, $insert_query);
        
        if ($result) {
            // Registration successful
            $_SESSION['success'] = "Registration successful! You can now log in.";
            header("Location: ../views/login.html");
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
            'address' => $address
        ];
        header("Location: ../views/employer_register.html");
        exit();
    }
    
} else {
    header("Location: ../views/employer_register.html");
    exit();
}

// Close the connection
pg_close($connection);
?>