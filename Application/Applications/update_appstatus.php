<?php
require_once '../database/connection.php';

function update_application_status($application_id, $status, $employer_id) {
    $conn = create_connection();
    
    $verify_query = "
        SELECT a.application_id 
        FROM Applications a
        INNER JOIN Internships i ON a.internship_id = i.internship_id
        WHERE a.application_id = $1 AND i.employer_id = $2
    ";
    
    $verify_result = pg_query_params($conn, $verify_query, array($application_id, $employer_id));
    
    if (!$verify_result || pg_num_rows($verify_result) === 0) {
        pg_close($conn);
        return false;
    }
    
    $valid_statuses = ['Pending', 'Approved', 'Rejected'];
    if (!in_array($status, $valid_statuses)) {
        pg_close($conn);
        return false;
    }
    
    $update_query = "
        UPDATE Applications 
        SET status = $1, updated_at = CURRENT_TIMESTAMP
        WHERE application_id = $2
    ";
    
    $update_result = pg_query_params($conn, $update_query, array($status, $application_id));
    
    $success = $update_result && pg_affected_rows($update_result) > 0;
    pg_close($conn);
    
    return $success;
}

function bulk_update_application_status($application_ids, $status, $employer_id) {
    $results = array();
    
    foreach ($application_ids as $application_id) {
        $success = update_application_status($application_id, $status, $employer_id);
        $results[$application_id] = $success;
    }
    
    return $results;
}

function get_application_statistics($employer_id) {
    $conn = create_connection();
    
    $query = "
        SELECT 
            a.status,
            COUNT(*) as count
        FROM Applications a
        INNER JOIN Internships i ON a.internship_id = i.internship_id
        WHERE i.employer_id = $1
        GROUP BY a.status
    ";
    
    $result = pg_query_params($conn, $query, array($employer_id));
    
    if (!$result) {
        pg_close($conn);
        return false;
    }
    
    $stats = array(
        'Pending' => 0,
        'Approved' => 0,
        'Rejected' => 0,
        'Total' => 0
    );
    
    while ($row = pg_fetch_assoc($result)) {
        $stats[$row['status']] = (int)$row['count'];
        $stats['Total'] += (int)$row['count'];
    }
    
    pg_close($conn);
    return $stats;
}

if (basename($_SERVER['PHP_SELF']) === 'update_application_status.php' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }
    
    if (isset($input['application_id']) && isset($input['status']) && isset($input['employer_id'])) {
        $success = update_application_status(
            $input['application_id'], 
            $input['status'], 
            $input['employer_id']
        );
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Application status updated successfully' : 'Failed to update application status'
        ]);
    } elseif (isset($input['application_ids']) && isset($input['status']) && isset($input['employer_id'])) {
        $results = bulk_update_application_status(
            $input['application_ids'], 
            $input['status'], 
            $input['employer_id']
        );
        
        $success_count = count(array_filter($results));
        $total_count = count($results);
        
        echo json_encode([
            'success' => $success_count > 0,
            'results' => $results,
            'message' => "Updated $success_count out of $total_count applications"
        ]);
    } else {
        echo json_encode(['error' => 'Missing required parameters']);
    }
    exit;
}

if (basename($_SERVER['PHP_SELF']) === 'update_application_status.php' && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['stats']) && isset($_GET['employer_id'])) {
    header('Content-Type: application/json');
    $stats = get_application_statistics($_GET['employer_id']);
    echo json_encode($stats);
    exit;
}
?>