<?php

require_once '../database/connection.php'; 

$conn = create_connection();


$location = $_GET['location'] ?? 'all';
$duration = $_GET['duration'] ?? 'all';
$industry = $_GET['industry'] ?? 'all';
$job_title = $_GET['job_title'] ?? '';
$company_name = $_GET['company_name'] ?? '';


$sql = "
    SELECT i.title, e.company_name, i.location, i.duration, 
           TO_CHAR(i.start_date, 'DD/MM/YYYY') AS start_date, 
           TO_CHAR(i.end_date, 'DD/MM/YYYY') AS end_date, 
           i.requirements
    FROM Internships i
    JOIN Employers e ON i.employer_id = e.employer_id
    WHERE 1=1
";

$params = [];
$index = 1;


if ($location !== 'all') {
    $sql .= " AND i.location = $" . $index;
    $params[] = $location;
    $index++;
}

if ($duration !== 'all') {
    $sql .= " AND i.duration = $" . $index;
    $params[] = $duration;
    $index++;
}

if ($industry !== 'all') {
    $sql .= " AND e.industry = $" . $index;
    $params[] = $industry;
    $index++;
}


if (!empty($job_title)) {
    $sql .= " AND LOWER(i.title) LIKE LOWER($" . $index . ")";
    $params[] = '%' . $job_title . '%';
    $index++;
}


if (!empty($company_name)) {
    $sql .= " AND LOWER(e.company_name) LIKE LOWER($" . $index . ")";
    $params[] = '%' . $company_name . '%';
    $index++;
}


$sql .= " ORDER BY i.title ASC, e.company_name ASC";


$result = pg_query_params($conn, $sql, $params);

if (!$result) {
    echo "Error in query: " . pg_last_error($conn);
    exit;
}


echo '<table border="1" cellpadding="10">';
echo '<tr>
        <th>Title</th>
        <th>Company</th>
        <th>Location</th>
        <th>Duration</th>
        <th>Start Date</th>
        <th>End Date</th>
        <th>Requirements</th>
      </tr>';

$row_count = 0;
while ($row = pg_fetch_assoc($result)) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($row['title']) . '</td>';
    echo '<td>' . htmlspecialchars($row['company_name']) . '</td>';
    echo '<td>' . htmlspecialchars($row['location']) . '</td>';
    echo '<td>' . htmlspecialchars($row['duration']) . '</td>';
    echo '<td>' . htmlspecialchars($row['start_date']) . '</td>';
    echo '<td>' . htmlspecialchars($row['end_date']) . '</td>';
    echo '<td>' . htmlspecialchars($row['requirements']) . '</td>';
    echo '</tr>';
    $row_count++;
}


if ($row_count === 0) {
    echo '<tr><td colspan="7" style="text-align: center; font-style: italic;">No internships found matching your criteria.</td></tr>';
}

echo '</table>';


echo '<p style="margin-top: 10px; font-size: 0.9em; color: #666;">';
echo "Found $row_count internship(s)";
if (!empty($job_title) || !empty($company_name) || $location !== 'all' || $duration !== 'all' || $industry !== 'all') {
    echo " matching your search criteria";
}
echo '</p>';


pg_close($conn);

?>