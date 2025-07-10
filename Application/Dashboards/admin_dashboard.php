<?php
session_start();
require_once '../database/connection.php';

require_once '../database/auth_helpers.php';
$user_id = get_current_user_id();
$user_type = get_current_user_type();

$conn = create_connection();

$employer_count_query = "SELECT COUNT(*) FROM \"Employers\"";
$employer_count_result = pg_query_params($conn, $employer_count_query, array());
$employer_count = pg_fetch_result($employer_count_result, 0, 0);

$student_count_query = "SELECT COUNT(*) FROM \"Students\"";
$student_count_result = pg_query_params($conn, $student_count_query, array());
$student_count = pg_fetch_result($student_count_result, 0, 0);

$active_jobs_query = "SELECT COUNT(*) FROM \"Internships\" WHERE is_active = TRUE";
$active_jobs_result = pg_query_params($conn, $active_jobs_query, array());
$active_jobs = pg_fetch_result($active_jobs_result, 0, 0);

$completed_internships_query = "SELECT COUNT(*) FROM \"Results\"";
$completed_internships_result = pg_query_params($conn, $completed_internships_query, array());
$completed_internships = pg_fetch_result($completed_internships_result, 0, 0);

$certificates_issued_query = "SELECT COUNT(*) FROM \"Certificates\"";
$certificates_issued_result = pg_query_params($conn, $certificates_issued_query, array());
$certificates_issued = pg_fetch_result($certificates_issued_result, 0, 0);

$recent_employers_query = "SELECT company_name, email, created_at FROM \"Employers\" ORDER BY created_at DESC LIMIT 4";
$recent_employers_result = pg_query_params($conn, $recent_employers_query, array());

$recent_students_query = "SELECT name, email, phone, department, academic_year, created_at FROM \"Students\" ORDER BY created_at DESC LIMIT 3";
$recent_students_result = pg_query_params($conn, $recent_students_query, array());

$recent_completions_sql = "
    SELECT r.completion_date, r.rating, s.name as student_name, 
           i.title as internship_title, e.company_name,
           c.certificate_id, a.application_id
    FROM \"Results\" r
    JOIN \"Applications\" a ON r.application_id = a.application_id
    JOIN \"Students\" s ON a.student_id = s.student_id
    JOIN \"Internships\" i ON a.internship_id = i.internship_id
    JOIN \"Employers\" e ON i.employer_id = e.employer_id
    LEFT JOIN \"Certificates\" c ON a.application_id = c.application_id
    ORDER BY r.completion_date DESC
    LIMIT 5
";
$recent_completions = pg_query($conn, $recent_completions_sql);

pg_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Intern Connect | Admin Dashboard</title>
  <link rel="icon" type="image/x-icon" href="/static/images/title.png">
  <link rel="stylesheet" href="../static/css/index.css">
</head>
<body>
  <svg class="bg-animation" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:-1;pointer-events:none;opacity:0.3;" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
    <path fill="url(#aurora-gradient)" d="M0,128L60,138.7C120,149,240,171,360,154.7C480,139,600,85,720,96C840,107,960,181,1080,197.3C1200,213,1320,171,1380,149.3L1440,128L1440,0L1380,0C1320,0,1200,0,1080,0C960,0,840,0,720,0C600,0,480,0,360,0C240,0,120,0,60,0L0,0Z"></path>
    <defs>
      <linearGradient id="aurora-gradient" x1="0" y1="0" x2="1" y2="1">
        <stop offset="0%" stop-color="#4f46e5"/>
        <stop offset="50%" stop-color="#06b6d4"/>
        <stop offset="100%" stop-color="#8b5cf6"/>
      </linearGradient>
    </defs>
  </svg>

  <header class="header">
    ADMIN DASHBOARD
  </header>
  <?php require_once '../includes/navigation.php'; echo render_navigation('dashboard'); ?>

  <div class="content">
    <h1 class="welcome">Welcome, Admin</h1>

    <div class="stats-container">
      <div class="stat-card">
        <div class="stat-number"><?php echo $employer_count; ?></div>
        <div class="stat-label">Total Employers</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo $student_count; ?></div>
        <div class="stat-label">Total Job Seekers</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo $active_jobs; ?></div>
        <div class="stat-label">Active Internships</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo $completed_internships; ?></div>
        <div class="stat-label">Completed Internships</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo $certificates_issued; ?></div>
        <div class="stat-label">Certificates Issued</div>
      </div>
    </div>

    <div class="recent-apps">
      <h2>Recent Employer Registrations</h2>
      <table class="apps-table">
        <thead>
          <tr>
            <th>Company</th>
            <th>Email</th>
            <th>Date Registered</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = pg_fetch_assoc($recent_employers_result)): ?>
            <tr>
              <td><?php echo htmlspecialchars($row['company_name']); ?></td>
              <td><?php echo htmlspecialchars($row['email']); ?></td>
              <td><?php echo date("d/m/Y", strtotime($row['created_at'])); ?></td>
              <td><button class="btn">View</button></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <div class="recent-apps">
      <h2>Recent Student Registrations</h2>
      <table class="apps-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Department</th>
            <th>Academic Year</th>
            <th>Date Registered</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = pg_fetch_assoc($recent_students_result)): ?>
            <tr>
              <td><?php echo htmlspecialchars($row['name']); ?></td>
              <td><?php echo htmlspecialchars($row['email']); ?></td>
              <td><?php echo htmlspecialchars($row['phone']); ?></td>
              <td><?php echo htmlspecialchars($row['department']); ?></td>
              <td><?php echo htmlspecialchars($row['academic_year']); ?></td>
              <td><?php echo date("d/m/Y", strtotime($row['created_at'])); ?></td>
              <td><button class="btn">View</button></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- Recent Completions Section -->
    <div class="recent-apps">
      <h2>Recent Internship Completions</h2>
      <?php if ($recent_completions && pg_num_rows($recent_completions) > 0): ?>
        <table class="apps-table">
          <thead>
            <tr>
              <th>Student</th>
              <th>Internship</th>
              <th>Company</th>
              <th>Rating</th>
              <th>Completed</th>
              <th>Certificate</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($completion = pg_fetch_assoc($recent_completions)): ?>
              <tr>
                <td><?php echo htmlspecialchars($completion['student_name']); ?></td>
                <td><?php echo htmlspecialchars($completion['internship_title']); ?></td>
                <td><?php echo htmlspecialchars($completion['company_name']); ?></td>
                <td>
                  <div style="color: #fbbf24;">
                    <?php 
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= $completion['rating'] ? '★' : '☆';
                    }
                    echo ' ' . $completion['rating'] . '/5';
                    ?>
                  </div>
                </td>
                <td><?php echo date('d/m/Y', strtotime($completion['completion_date'])); ?></td>
                <td>
                  <?php if ($completion['certificate_id']): ?>
                    <span style="color: #22c55e;">✅ Issued</span>
                  <?php else: ?>
                    <span style="color: #fbbf24;">⏳ Pending</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!$completion['certificate_id']): ?>
                    <a href="../Certificates/issue_certificate.php?application_id=<?php echo $completion['application_id']; ?>" class="btn">Issue Cert</a>
                  <?php else: ?>
                    <a href="../Results/view_results.php?application_id=<?php echo $completion['application_id']; ?>" class="btn">View</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p style="color: rgba(255,255,255,0.7);">No completed internships yet.</p>
      <?php endif; ?>
    </div>
  </div>

  <footer class="footer">
    Intern Connect &copy; 2025 | All Rights Reserved
  </footer>
</body>
</html>
