<?php
require_once '../database/connection.php';

function search_applications($employer_id, $filters = array()) {
    $conn = create_connection();
    
    $base_query = "
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
        FROM Applications a
        INNER JOIN Students s ON a.student_id = s.student_id
        INNER JOIN Internships i ON a.internship_id = i.internship_id
        WHERE i.employer_id = $1
    ";
    
    $params = array($employer_id);
    $param_count = 1;
    $where_conditions = array();
    
    if (!empty($filters['status'])) {
        $param_count++;
        $where_conditions[] = "a.status = $" . $param_count;
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['internship_id'])) {
        $param_count++;
        $where_conditions[] = "a.internship_id = $" . $param_count;
        $params[] = $filters['internship_id'];
    }
    
    if (!empty($filters['department'])) {
        $param_count++;
        $where_conditions[] = "s.department ILIKE $" . $param_count;
        $params[] = '%' . $filters['department'] . '%';
    }
    
    if (!empty($filters['academic_year'])) {
        $param_count++;
        $where_conditions[] = "s.academic_year = $" . $param_count;
        $params[] = $filters['academic_year'];
    }
    
    if (!empty($filters['student_name'])) {
        $param_count++;
        $where_conditions[] = "s.name ILIKE $" . $param_count;
        $params[] = '%' . $filters['student_name'] . '%';
    }
    
    if (!empty($filters['email'])) {
        $param_count++;
        $where_conditions[] = "s.email ILIKE $" . $param_count;
        $params[] = '%' . $filters['email'] . '%';
    }
    
    if (!empty($filters['date_from'])) {
        $param_count++;
        $where_conditions[] = "a.application_date >= $" . $param_count;
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $param_count++;
        $where_conditions[] = "a.application_date <= $" . $param_count;
        $params[] = $filters['date_to'] . ' 23:59:59';
    }
    
    if (!empty($where_conditions)) {
        $base_query .= " AND " . implode(" AND ", $where_conditions);
    }
    
    $sort_by = $filters['sort_by'] ?? 'application_date';
    $sort_order = $filters['sort_order'] ?? 'DESC';
    
    $valid_sort_columns = [
        'application_date', 'student_name', 'status', 'internship_title', 'department', 'academic_year'
    ];
    
    if (in_array($sort_by, $valid_sort_columns)) {
        if ($sort_by === 'student_name') {
            $base_query .= " ORDER BY s.name " . ($sort_order === 'ASC' ? 'ASC' : 'DESC');
        } elseif ($sort_by === 'internship_title') {
            $base_query .= " ORDER BY i.title " . ($sort_order === 'ASC' ? 'ASC' : 'DESC');
        } else {
            $base_query .= " ORDER BY a." . $sort_by . " " . ($sort_order === 'ASC' ? 'ASC' : 'DESC');
        }
    } else {
        $base_query .= " ORDER BY a.application_date DESC";
    }
    
    if (!empty($filters['limit'])) {
        $param_count++;
        $base_query .= " LIMIT $" . $param_count;
        $params[] = (int)$filters['limit'];
    }
    
    if (!empty($filters['offset'])) {
        $param_count++;
        $base_query .= " OFFSET $" . $param_count;
        $params[] = (int)$filters['offset'];
    }
    
    $result = pg_query_params($conn, $base_query, $params);
    
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

function get_filter_options($employer_id) {
    $conn = create_connection();
    
    $internships_query = "
        SELECT internship_id, title 
        FROM Internships 
        WHERE employer_id = $1 
        ORDER BY title
    ";
    $internships_result = pg_query_params($conn, $internships_query, array($employer_id));
    $internships = array();
    while ($row = pg_fetch_assoc($internships_result)) {
        $internships[] = $row;
    }
    
    $departments_query = "
        SELECT DISTINCT s.department 
        FROM Students s
        INNER JOIN Applications a ON s.student_id = a.student_id
        INNER JOIN Internships i ON a.internship_id = i.internship_id
        WHERE i.employer_id = $1 AND s.department IS NOT NULL
        ORDER BY s.department
    ";
    $departments_result = pg_query_params($conn, $departments_query, array($employer_id));
    $departments = array();
    while ($row = pg_fetch_assoc($departments_result)) {
        $departments[] = $row['department'];
    }
    
    $years_query = "
        SELECT DISTINCT s.academic_year 
        FROM Students s
        INNER JOIN Applications a ON s.student_id = a.student_id
        INNER JOIN Internships i ON a.internship_id = i.internship_id
        WHERE i.employer_id = $1 AND s.academic_year IS NOT NULL
        ORDER BY s.academic_year
    ";
    $years_result = pg_query_params($conn, $years_query, array($employer_id));
    $academic_years = array();
    while ($row = pg_fetch_assoc($years_result)) {
        $academic_years[] = $row['academic_year'];
    }
    
    pg_close($conn);
    
    return array(
        'internships' => $internships,
        'departments' => $departments,
        'academic_years' => $academic_years,
        'statuses' => ['Pending', 'Approved', 'Rejected']
    );
}

function get_application_count($employer_id, $filters = array()) {
    $conn = create_connection();
    
    $base_query = "
        SELECT COUNT(*) as total
        FROM Applications a
        INNER JOIN Students s ON a.student_id = s.student_id
        INNER JOIN Internships i ON a.internship_id = i.internship_id
        WHERE i.employer_id = $1
    ";
    
    $params = array($employer_id);
    $param_count = 1;
    $where_conditions = array();
    
    if (!empty($filters['status'])) {
        $param_count++;
        $where_conditions[] = "a.status = $" . $param_count;
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['internship_id'])) {
        $param_count++;
        $where_conditions[] = "a.internship_id = $" . $param_count;
        $params[] = $filters['internship_id'];
    }
    
    if (!empty($filters['department'])) {
        $param_count++;
        $where_conditions[] = "s.department ILIKE $" . $param_count;
        $params[] = '%' . $filters['department'] . '%';
    }
    
    if (!empty($filters['academic_year'])) {
        $param_count++;
        $where_conditions[] = "s.academic_year = $" . $param_count;
        $params[] = $filters['academic_year'];
    }
    
    if (!empty($filters['student_name'])) {
        $param_count++;
        $where_conditions[] = "s.name ILIKE $" . $param_count;
        $params[] = '%' . $filters['student_name'] . '%';
    }
    
    if (!empty($filters['email'])) {
        $param_count++;
        $where_conditions[] = "s.email ILIKE $" . $param_count;
        $params[] = '%' . $filters['email'] . '%';
    }
    
    if (!empty($filters['date_from'])) {
        $param_count++;
        $where_conditions[] = "a.application_date >= $" . $param_count;
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $param_count++;
        $where_conditions[] = "a.application_date <= $" . $param_count;
        $params[] = $filters['date_to'] . ' 23:59:59';
    }
    
    if (!empty($where_conditions)) {
        $base_query .= " AND " . implode(" AND ", $where_conditions);
    }
    
    $result = pg_query_params($conn, $base_query, $params);
    
    if (!$result) {
        pg_close($conn);
        return 0;
    }
    
    $row = pg_fetch_assoc($result);
    pg_close($conn);
    
    return (int)$row['total'];
}

if (basename($_SERVER['PHP_SELF']) === 'search_applications.php' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'search':
                if (!isset($_GET['employer_id'])) {
                    echo json_encode(['error' => 'Missing employer_id']);
                    exit;
                }
                
                $filters = array();
                $allowed_filters = [
                    'status', 'internship_id', 'department', 'academic_year', 
                    'student_name', 'email', 'date_from', 'date_to',
                    'sort_by', 'sort_order', 'limit', 'offset'
                ];
                
                foreach ($allowed_filters as $filter) {
                    if (isset($_GET[$filter]) && $_GET[$filter] !== '') {
                        $filters[$filter] = $_GET[$filter];
                    }
                }
                
                $applications = search_applications($_GET['employer_id'], $filters);
                $total_count = get_application_count($_GET['employer_id'], $filters);
                
                echo json_encode([
                    'applications' => $applications,
                    'total_count' => $total_count,
                    'filters_applied' => $filters
                ]);
                break;
                
            case 'filter_options':
                if (!isset($_GET['employer_id'])) {
                    echo json_encode(['error' => 'Missing employer_id']);
                    exit;
                }
                
                $options = get_filter_options($_GET['employer_id']);
                echo json_encode($options);
                break;
                
            default:
                echo json_encode(['error' => 'Invalid action']);
        }
    } else {
        echo json_encode(['error' => 'Missing action parameter']);
    }
    exit;
}
?>