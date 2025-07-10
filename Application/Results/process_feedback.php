<?php
session_start();
require_once '../database/connection.php';
require_once '../database/auth_helpers.php';
require_auth();

$conn = create_connection();

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../Dashboards/' . $_SESSION['user_type'] . '_dashboard.php');
    exit();
}

$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    if ($action === 'student_feedback') {
        // Handle student feedback submission
        $application_id = $_POST['application_id'] ?? null;
        $student_feedback = trim($_POST['student_feedback'] ?? '');
        
        if (!$application_id || empty($student_feedback)) {
            throw new Exception('Application ID and feedback are required.');
        }
        
        // Verify student owns this application and result exists
        $verify_sql = "
            SELECT r.result_id, a.student_id 
            FROM \"Results\" r
            JOIN \"Applications\" a ON r.application_id = a.application_id
            WHERE a.application_id = $1
        ";
        $verify_result = pg_query_params($conn, $verify_sql, [$application_id]);
        
        if (!$verify_result || pg_num_rows($verify_result) === 0) {
            throw new Exception('Result not found.');
        }
        
        $verify_data = pg_fetch_assoc($verify_result);
        
        if ($user_type === 'student' && $verify_data['student_id'] !== $user_id) {
            throw new Exception('Access denied.');
        }
        
        // Update the result with student feedback
        $update_sql = "
            UPDATE \"Results\" 
            SET student_feedback = $1, updated_at = CURRENT_TIMESTAMP
            WHERE result_id = $2
        ";
        $update_result = pg_query_params($conn, $update_sql, [$student_feedback, $verify_data['result_id']]);
        
        if (!$update_result) {
            throw new Exception('Failed to save student feedback.');
        }
        
        $_SESSION['success_message'] = 'Your feedback has been submitted successfully!';
        header('Location: view_results.php?application_id=' . $application_id);
        exit();
        
    } elseif ($action === 'employer_feedback') {
        // Handle employer feedback submission (from feedback_form.php)
        $application_id = $_POST['application_id'] ?? null;
        $employer_feedback = trim($_POST['employer_feedback'] ?? '');
        $rating = intval($_POST['rating'] ?? 0);
        $completion_date = $_POST['completion_date'] ?? date('Y-m-d');
        
        if (!$application_id || empty($employer_feedback) || $rating < 1 || $rating > 5) {
            throw new Exception('All fields are required and rating must be between 1-5.');
        }
        
        // Verify employer/admin access to this application
        $verify_sql = "
            SELECT a.application_id, i.employer_id, a.status
            FROM \"Applications\" a
            JOIN \"Internships\" i ON a.internship_id = i.internship_id
            LEFT JOIN \"Results\" r ON a.application_id = r.application_id
            WHERE a.application_id = $1 AND r.result_id IS NULL
        ";
        $verify_result = pg_query_params($conn, $verify_sql, [$application_id]);
        
        if (!$verify_result || pg_num_rows($verify_result) === 0) {
            throw new Exception('Application not found or feedback already exists.');
        }
        
        $verify_data = pg_fetch_assoc($verify_result);
        
        if ($user_type === 'employer' && $verify_data['employer_id'] !== $user_id) {
            throw new Exception('Access denied.');
        }
        
        if ($verify_data['status'] !== 'Approved') {
            throw new Exception('Only approved applications can be completed.');
        }
        
        // Insert the result
        $insert_sql = "
            INSERT INTO \"Results\" (application_id, employer_feedback, rating, completion_date)
            VALUES ($1, $2, $3, $4)
        ";
        $insert_result = pg_query_params($conn, $insert_sql, [
            $application_id,
            $employer_feedback,
            $rating,
            $completion_date
        ]);
        
        if (!$insert_result) {
            throw new Exception('Failed to save employer feedback.');
        }
        
        $_SESSION['success_message'] = 'Internship completed successfully! Feedback has been recorded.';
        header('Location: complete_internship.php');
        exit();
        
    } elseif ($action === 'update_feedback') {
        // Handle feedback updates (for admins or original submitters)
        $result_id = $_POST['result_id'] ?? null;
        $employer_feedback = trim($_POST['employer_feedback'] ?? '');
        $student_feedback = trim($_POST['student_feedback'] ?? '');
        $rating = intval($_POST['rating'] ?? 0);
        
        if (!$result_id) {
            throw new Exception('Result ID is required.');
        }
        
        // Verify access to this result
        $verify_sql = "
            SELECT r.result_id, a.student_id, i.employer_id
            FROM \"Results\" r
            JOIN \"Applications\" a ON r.application_id = a.application_id
            JOIN \"Internships\" i ON a.internship_id = i.internship_id
            WHERE r.result_id = $1
        ";
        $verify_result = pg_query_params($conn, $verify_sql, [$result_id]);
        
        if (!$verify_result || pg_num_rows($verify_result) === 0) {
            throw new Exception('Result not found.');
        }
        
        $verify_data = pg_fetch_assoc($verify_result);
        $has_access = false;
        
        if ($user_type === 'admin') {
            $has_access = true;
        } elseif ($user_type === 'employer' && $verify_data['employer_id'] === $user_id) {
            $has_access = true;
        } elseif ($user_type === 'student' && $verify_data['student_id'] === $user_id) {
            $has_access = true;
        }
        
        if (!$has_access) {
            throw new Exception('Access denied.');
        }
        
        // Build update query based on user type and provided data
        $update_fields = [];
        $update_params = [];
        $param_count = 0;
        
        if ($user_type === 'employer' || $user_type === 'admin') {
            if (!empty($employer_feedback)) {
                $update_fields[] = "employer_feedback = $" . (++$param_count);
                $update_params[] = $employer_feedback;
            }
            if ($rating >= 1 && $rating <= 5) {
                $update_fields[] = "rating = $" . (++$param_count);
                $update_params[] = $rating;
            }
        }
        
        if ($user_type === 'student' || $user_type === 'admin') {
            if (!empty($student_feedback)) {
                $update_fields[] = "student_feedback = $" . (++$param_count);
                $update_params[] = $student_feedback;
            }
        }
        
        if (empty($update_fields)) {
            throw new Exception('No valid fields to update.');
        }
        
        $update_fields[] = "updated_at = CURRENT_TIMESTAMP";
        $update_params[] = $result_id;
        
        $update_sql = "
            UPDATE \"Results\" 
            SET " . implode(', ', $update_fields) . "
            WHERE result_id = $" . (++$param_count);
        
        $update_result = pg_query_params($conn, $update_sql, $update_params);
        
        if (!$update_result) {
            throw new Exception('Failed to update feedback.');
        }
        
        $_SESSION['success_message'] = 'Feedback updated successfully!';
        
        // Redirect based on user type
        if (isset($_POST['redirect_to'])) {
            header('Location: ' . $_POST['redirect_to']);
        } else {
            header('Location: view_results.php?application_id=' . ($_POST['application_id'] ?? ''));
        }
        exit();
        
    } elseif ($action === 'delete_feedback') {
        // Handle feedback deletion (admin only)
        if ($user_type !== 'admin') {
            throw new Exception('Only administrators can delete feedback.');
        }
        
        $result_id = $_POST['result_id'] ?? null;
        
        if (!$result_id) {
            throw new Exception('Result ID is required.');
        }
        
        // Check if certificate exists for this result
        $cert_check_sql = "
            SELECT c.certificate_id 
            FROM \"Certificates\" c
            JOIN \"Results\" r ON c.application_id = r.application_id
            WHERE r.result_id = $1
        ";
        $cert_result = pg_query_params($conn, $cert_check_sql, [$result_id]);
        
        if ($cert_result && pg_num_rows($cert_result) > 0) {
            throw new Exception('Cannot delete feedback - certificate has been issued for this completion.');
        }
        
        // Delete the result
        $delete_sql = "DELETE FROM \"Results\" WHERE result_id = $1";
        $delete_result = pg_query_params($conn, $delete_sql, [$result_id]);
        
        if (!$delete_result) {
            throw new Exception('Failed to delete feedback.');
        }
        
        $_SESSION['success_message'] = 'Feedback deleted successfully.';
        header('Location: complete_internship.php');
        exit();
        
    } else {
        throw new Exception('Invalid action specified.');
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    
    // Redirect to appropriate page based on action and user type
    $redirect_page = 'complete_internship.php';
    
    if (isset($_POST['application_id'])) {
        $redirect_page = 'view_results.php?application_id=' . $_POST['application_id'];
    } elseif ($user_type === 'student') {
        $redirect_page = '../Dashboards/student_dashboard.php';
    }
    
    header('Location: ' . $redirect_page);
    exit();
}

pg_close($conn);
?>
