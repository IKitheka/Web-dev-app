<?php
require_once 'connection.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? '';

    if (empty($email) || empty($password) || empty($user_type)) {
        $_SESSION['error'] = "Email, password, and account type are required";
        header("Location: ../Authentication/login.php");
        exit();
    }

    $conn = create_connection();

    // Map user_type to table/columns
    switch ($user_type) {
        case 'student':
            $table = '"Students"';
            $id_col = 'student_id';
            $name_col = 'name';
            $password_col = 'password_hash';
            $status_check = ""; // No status column
            $dashboard = '../Dashboards/student_dashboard.php';
            break;
        case 'employer':
            $table = '"Employers"';
            $id_col = 'employer_id';
            $name_col = 'company_name';
            $password_col = 'password_hash';
            $status_check = ""; // No status column
            $dashboard = '../Dashboards/employer_dashboard.php';
            break;
        case 'admin':
        case 'administrator':
            $table = '"Administrators"';
            $id_col = 'admin_id';
            $name_col = 'full_name';
            $password_col = 'password_hash';
            $status_check = ""; // No status column
            $dashboard = '../Dashboards/admin_dashboard.php';
            break;
        default:
            $_SESSION['error'] = "Invalid account type";
            header("Location: ../Authentication/login.php");
            exit();
    }

    $sql = "SELECT $id_col, email, $password_col, $name_col FROM $table WHERE email = $1 $status_check";
    $result = pg_query_params($conn, $sql, [$email]);

    if ($result && $user = pg_fetch_assoc($result)) {
        if (password_verify($password, $user[$password_col])) {
            // Set session variables
            $_SESSION['user_id'] = $user[$id_col];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user[$name_col];
            $_SESSION['user_type'] = $user_type;
            $_SESSION['logged_in'] = true;

            // Also set specific session variable for dashboards
            switch ($user_type) {
                case 'student':
                    $_SESSION['student_id'] = $user[$id_col];
                    break;
                case 'employer':
                    $_SESSION['employer_id'] = $user[$id_col];
                    break;
                case 'admin':
                case 'administrator':
                    $_SESSION['admin_id'] = $user[$id_col];
                    break;
            }

            header("Location: $dashboard");
            exit();
        } else {
            $_SESSION['error'] = "Invalid email or password";
            header("Location: ../Authentication/login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Invalid email or password";
        header("Location: ../Authentication/login.php");
        exit();
    }
}
?>