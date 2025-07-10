<?php
require_once '../database/connection.php';

function update_application_status($application_id, $status) {
    $conn = create_connection();
    $valid_statuses = ['Pending', 'Approved', 'Rejected'];
    if (!in_array($status, $valid_statuses)) {
        pg_close($conn);
        return false;
    }
    $update_query = "
        UPDATE \"Applications\" 
        SET status = $1, updated_at = CURRENT_TIMESTAMP
        WHERE application_id = $2
    ";
    $update_result = pg_query_params($conn, $update_query, array($status, $application_id));
    $success = $update_result && pg_affected_rows($update_result) > 0;
    pg_close($conn);
    return $success;
}

function bulk_update_application_status($application_ids, $status) {
    $results = array();
    foreach ($application_ids as $application_id) {
        $success = update_application_status($application_id, $status);
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
        FROM \"Applications\" a
        INNER JOIN \"Internships\" i ON a.internship_id = i.internship_id
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: text/plain');
    if (isset($_POST['application_id']) && isset($_POST['status'])) {
        $success = update_application_status(
            $_POST['application_id'], 
            $_POST['status']
        );
        echo $success ? 'Application status updated successfully' : 'Failed to update application status';
        exit;
    } elseif (isset($_POST['application_ids']) && isset($_POST['status']) && is_array($_POST['application_ids'])) {
        $results = bulk_update_application_status(
            $_POST['application_ids'], 
            $_POST['status']
        );
        $success_count = count(array_filter($results));
        $total_count = count($results);
        echo "Updated $success_count out of $total_count applications";
        exit;
    } else {
        echo 'Missing required parameters';
        exit;
    }
}
?>