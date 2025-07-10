<?php
session_start();
require_once '../database/connection.php';
require_once '../database/auth_helpers.php';

require_auth("student");

$student_id = get_current_user_id();

$conn = @create_connection();
if (!$conn) {
    echo '<!DOCTYPE html><html><head><title>Error</title></head><body><div style="color:red;text-align:center;margin-top:50px;">Database connection failed. Please try again later.</div></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'])) {
    $application_id = $_POST['application_id'];
    if (withdraw_application($conn, $application_id, $student_id)) {
        $_SESSION['success'] = 'Application withdrawn successfully.';
        header('Location: my_applications.php');
        exit;
    } else {
        $_SESSION['success'] = 'Failed to withdraw application. Please try again.';
        header('Location: my_applications.php');
        exit;
    }
}

// Optional: Show success message if set in session
$success_message = $_SESSION['success'] ?? '';
if (isset($_SESSION['success'])) unset($_SESSION['success']);
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
  <?php require_once '../includes/navigation.php'; echo render_navigation('my_applications'); ?>
  <div class="content">
    <h1 class="welcome">Application History</h1>
    <?php if ($success_message): ?>
      <div class="message message-success" style="margin-bottom: 20px; text-align: center;">✔️ <?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
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
        $sql = "SELECT e.company_name, i.title, TO_CHAR(a.application_date, 'DD/MM/YYYY') AS date_applied, a.status, a.application_id FROM \"Applications\" a JOIN \"Internships\" i ON a.internship_id = i.internship_id JOIN \"Employers\" e ON i.employer_id = e.employer_id WHERE a.student_id = $1 ORDER BY a.application_date DESC";
        $conn = @create_connection();
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
              echo '<form method="POST" style="display:inline;"><input type="hidden" name="application_id" value="' . htmlspecialchars($row['application_id']) . '"><button class="btn" type="submit">Withdraw</button></form>';
            } else if ($status === 'Approved') {
              // Check if result exists for this application
              $app_id = $row['application_id'];
              $result_check = pg_query_params($conn, 'SELECT 1 FROM "Results" WHERE application_id = $1', [$app_id]);
              if ($result_check && pg_num_rows($result_check) > 0) {
                echo '<a class="btn" href="../Results/view_results.php?application_id=' . htmlspecialchars($app_id) . '">View</a>';
              } else {
                echo '<button class="btn" disabled>In Progress</button>';
              }
            } else {
              echo '<button class="btn" disabled>Withdraw</button>';
            }
            echo '</td>';
            echo '</tr>';
            $row_count++;
          }
          if ($row_count === 0) {
            echo '<tr><td colspan="5" style="text-align: center; font-style: italic;">No applications found.</td></tr>';
          }
        } else {
          echo '<tr><td colspan="5" style="color:red; text-align:center;">Error fetching applications. Please try again later.</td></tr>';
        }
        ?>
        </tbody>
      </table>
    </div>
  </div>
  <footer class="footer">Intern Connect &copy; 2025 | All Rights Reserved</footer>
  <?php if ($conn) { pg_close($conn); } ?>
</body>
</html> 