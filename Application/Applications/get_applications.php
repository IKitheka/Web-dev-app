<?php
require_once '../database/connection.php';

function get_employer_applications($employer_id) {
    $conn = create_connection();
    
    $query = "
        SELECT 
            a.application_id,
            a.student_id,
            a.internship_id,
            a.application_date,
            a.status,
            a.cover_letter,
            a.resume_url,
            a.updated_at,
            s.name as student_name,
            s.email as student_email,
            s.phone as student_phone,
            s.department,
            s.academic_year,
            i.title as internship_title,
            i.location as internship_location
        FROM \"Applications\" a
        INNER JOIN \"Students\" s ON a.student_id = s.student_id
        INNER JOIN \"Internships\" i ON a.internship_id = i.internship_id
        WHERE i.employer_id = $1
        ORDER BY a.application_date DESC
    ";
    
    $result = pg_query_params($conn, $query, array($employer_id));
    
    if (!$result) {
        pg_close($conn);
        return false;
    }
    
    $applications = array();
    while ($row = pg_fetch_assoc($result)) {
        $applications[] = $row;
    }
    
    pg_close($conn);
    return $applications;
}

function get_internship_applications($internship_id) {
    $conn = create_connection();
    
    $query = "
        SELECT 
            a.application_id,
            a.student_id,
            a.application_date,
            a.status,
            a.cover_letter,
            a.resume_url,
            a.updated_at,
            s.name as student_name,
            s.email as student_email,
            s.phone as student_phone,
            s.department,
            s.academic_year,
            i.title as internship_title
        FROM \"Applications\" a
        INNER JOIN \"Students\" s ON a.student_id = s.student_id
        INNER JOIN \"Internships\" i ON a.internship_id = i.internship_id
        WHERE a.internship_id = $1
        ORDER BY a.application_date DESC
    ";
    
    $result = pg_query_params($conn, $query, array($internship_id));
    
    if (!$result) {
        pg_close($conn);
        return false;
    }
    
    $applications = array();
    while ($row = pg_fetch_assoc($result)) {
        $applications[] = $row;
    }
    
    pg_close($conn);
    return $applications;
}

function get_application_details($application_id) {
    $conn = create_connection();
    
    $query = "
        SELECT 
            a.*,
            s.name as student_name,
            s.email as student_email,
            s.phone as student_phone,
            s.department,
            s.academic_year,
            i.title as internship_title,
            i.description as internship_description,
            i.location as internship_location,
            i.duration,
            e.company_name
        FROM \"Applications\" a
        INNER JOIN \"Students\" s ON a.student_id = s.student_id
        INNER JOIN \"Internships\" i ON a.internship_id = i.internship_id
        INNER JOIN \"Employers\" e ON i.employer_id = e.employer_id
        WHERE a.application_id = $1
    ";
    
    $result = pg_query_params($conn, $query, array($application_id));
    
    if (!$result) {
        pg_close($conn);
        return false;
    }
    
    $application = pg_fetch_assoc($result);
    pg_close($conn);
    
    return $application;
}

if (basename($_SERVER['PHP_SELF']) === 'get_applications.php' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    
    if (isset($_GET['employer_id'])) {
        $applications = get_employer_applications($_GET['employer_id']);
        echo json_encode($applications);
    } elseif (isset($_GET['internship_id'])) {
        $applications = get_internship_applications($_GET['internship_id']);
        echo json_encode($applications);
    } elseif (isset($_GET['application_id'])) {
        $application = get_application_details($_GET['application_id']);
        echo json_encode($application);
    } else {
        echo json_encode(['error' => 'Missing required parameters']);
    }
    exit;
}
?>