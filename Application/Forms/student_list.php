<?php
require_once '../database/connection.php';
$conn = create_connection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Intern Connect | Students</title>
  <link rel="icon" href="../static/images/title.png">
  <link rel="stylesheet" href="../static/css/index.css">
</head>
<body>
  <!-- Aurora Background -->
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
  <header class="header">STUDENT ACCOUNTS</header>
  <nav class="nav">
    <a href="#">INTERN CONNECT</a>
    <a href="#">Dashboard</a>
    <a href="#">Employers</a>
    <a class="active" href="#">Students</a>
    <a href="#">Profile</a>
    <a href="#" style="margin-left: auto;">Log out</a>
  </nav>
  <div class="content">
    <h1 class="welcome">Registered Students</h1>
    <div class="table-section">
      <table class="apps-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Department</th>
            <th>Academic Year</th>
            <th>Phone</th>
            <th>Date Joined</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $sql = "SELECT name, email, department, academic_year, phone, TO_CHAR(date_joined, 'DD/MM/YYYY') AS date_joined, student_id FROM Students ORDER BY name ASC";
        $result = pg_query($conn, $sql);
        if ($result) {
          $row_count = 0;
          while ($row = pg_fetch_assoc($result)) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['email']) . '</td>';
            echo '<td>' . htmlspecialchars($row['department']) . '</td>';
            echo '<td>' . htmlspecialchars($row['academic_year']) . '</td>';
            echo '<td>' . htmlspecialchars($row['phone']) . '</td>';
            echo '<td>' . htmlspecialchars($row['date_joined']) . '</td>';
            echo '<td><form method="GET" action="view_student.php" style="display:inline;"><input type="hidden" name="student_id" value="' . htmlspecialchars($row['student_id']) . '"><button class="btn" type="submit">View</button></form></td>';
            echo '</tr>';
            $row_count++;
          }
          if ($row_count === 0) {
            echo '<tr><td colspan="7" style="text-align: center; font-style: italic;">No students found.</td></tr>';
          }
        } else {
          echo '<tr><td colspan="7" style="color:red;">Error fetching students.</td></tr>';
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