<?php
session_start();
require_once '../database/connection.php';
$conn = create_connection();
$student_id = $_SESSION['student_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Intern Connect | My Applications</title>
  <link rel="icon" type="image/x-icon" href="../static/images/title.png">
  <link rel="stylesheet" href="../static/css/index.css">
</head>
<body>
  <!-- Background Animation -->
  <svg class="bg-animation" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
    <path fill="url(#aurora-gradient)" d="M0,128L60,138.7C120,149,240,171,360,154.7C480,139,600,85,720,96C840,107,960,181,1080,197.3C1200,213,1320,171,1380,149.3L1440,128L1440,0L1380,0C1320,0,1200,0,1080,0C960,0,840,0,720,0C600,0,480,0,360,0C240,0,120,0,60,0L0,0Z"></path>
    <defs>
      <linearGradient id="aurora-gradient" x1="0" y1="0" x2="1" y2="1">
        <stop offset="0%" stop-color="#4f46e5"/>
        <stop offset="50%" stop-color="#06b6d4"/>
        <stop offset="100%" stop-color="#8b5cf6"/>
      </linearGradient>
    </defs>
  </svg>
  <header class="header">MY APPLICATIONS</header>
  <nav class="nav">
    <a href="#">INTERN CONNECT</a>
    <a href="#">Dashboard</a>
    <a href="#">Browse Internships</a>
    <a href="#" class="active">My Applications</a>
    <a href="#">Profile</a>
    <a href="#" style="margin-left: auto;">Log out</a>
  </nav>
  <div class="content">
    <h1 class="welcome">Application History</h1>
    <div class="table-section">
      <h2>Submitted Applications</h2>
      <table class="apps-table">
        <thead>
          <tr>
            <th>Company</th>
            <th>Internship Title</th>
            <th>Date Applied</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
        if ($student_id) {
          $sql = "SELECT e.company_name, i.title, TO_CHAR(a.date_applied, 'DD/MM/YYYY') AS date_applied, a.status, a.application_id FROM Applications a JOIN Internships i ON a.internship_id = i.internship_id JOIN Employers e ON i.employer_id = e.employer_id WHERE a.student_id = $1 ORDER BY a.date_applied DESC";
          $result = pg_query_params($conn, $sql, [$student_id]);
          if ($result) {
            $row_count = 0;
            while ($row = pg_fetch_assoc($result)) {
              echo '<tr>';
              echo '<td>' . htmlspecialchars($row['company_name']) . '</td>';
              echo '<td>' . htmlspecialchars($row['title']) . '</td>';
              echo '<td>' . htmlspecialchars($row['date_applied']) . '</td>';
              $status = htmlspecialchars($row['status']);
              $status_class = $status === 'Approved' ? 'status-approved' : ($status === 'Pending' ? 'status-pending' : 'status-rejected');
              echo '<td><span class="' . $status_class . '">' . $status . '</span></td>';
              echo '<td>';
              if ($status === 'Pending') {
                echo '<form method="POST" action="withdraw_application.php" style="display:inline;"><input type="hidden" name="application_id" value="' . htmlspecialchars($row['application_id']) . '"><button class="btn" type="submit">Withdraw</button></form>';
              } else {
                echo '<button class="btn" disabled>' . ($status === 'Approved' ? 'View' : 'Withdraw') . '</button>';
              }
              echo '</td>';
              echo '</tr>';
              $row_count++;
            }
            if ($row_count === 0) {
              echo '<tr><td colspan="5" style="text-align: center; font-style: italic;">No applications found.</td></tr>';
            }
          } else {
            echo '<tr><td colspan="5" style="color:red;">Error fetching applications.</td></tr>';
          }
        } else {
          echo '<tr><td colspan="5" style="color:red;">Not logged in.</td></tr>';
        }
        pg_close($conn);
        ?>
        </tbody>
      </table>
    </div>
  </div>
  <footer class="footer">Intern Connect &copy; 2025 | All Rights Reserved</footer>
</body>
</html> 