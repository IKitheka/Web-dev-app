<?php
require_once 'connection.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = $_POST['email_add'];
    $password = $_POST['pass'];
    $accounttype = $_POST['acc_type'];
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Email and password are required";
        header("Location: login.php");
        exit();
    }
    
    try {
        // Fix: You cannot use variables for table names in prepared statements
        // Use string concatenation instead (be careful with this approach)
        $sql = "SELECT id, email, password, name FROM " . $accounttype . " WHERE email = ? AND status = 'active'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check login
        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['logged_in'] = true;
            
            header("Location: dashboard.php");
            exit();
        } else {
            // Login failed
            $_SESSION['error'] = "Invalid email or password";
            header("Location: login.php");
            exit();
        }
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Login error. Please try again.";
        header("Location: login.php");
        exit();
    }
}

// Helper functions
function is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function logout() {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>