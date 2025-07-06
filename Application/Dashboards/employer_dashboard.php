<?php
session_start();
include '../database/connection.php';
$conn = create_connection();

$employer_id = $_SESSION['employer_id'];

if (!$employer_id) {
    header('Location: ../process_login.php');
    exit();
}

// Get counts
$jobs_posted = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM Internships WHERE employer_id = '$employer_id'"), 0, 0);

$applications_received = pg_fetch_result(pg_query($conn, "
  SELECT COUNT(*) FROM Applications a
  JOIN Internships i ON a.internship_id = i.internship_id
  WHERE i.employer_id = '$employer_id'
"), 0, 0);

$shortlisted = pg_fetch_result(pg_query($conn, "
  SELECT COUNT(*) FROM Applications a
  JOIN Internships i ON a.internship_id = i.internship_id
  WHERE i.employer_id = '$employer_id' AND a.status = 'Approved'
"), 0, 0);

$interviews_scheduled = pg_fetch_result(pg_query($conn, "
  SELECT COUNT(*) FROM Applications a
  JOIN Internships i ON a.internship_id = i.internship_id
  WHERE i.employer_id = '$employer_id' AND a.status = 'Interview Scheduled'
"), 0, 0);

// Get recent applicants
$recent_applicants = pg_query($conn, "
  SELECT s.name AS student_name, i.title AS position, a.status, a.application_date
  FROM Applications a
  JOIN Students s ON a.student_id = s.student_id
  JOIN Internships i ON a.internship_id = i.internship_id
  WHERE i.employer_id = '$employer_id'
  ORDER BY a.application_date DESC
  LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Employer Dashboard</title>
  <link rel="stylesheet" href="Employer Dashboard.css" />
</head>
<body>
  <div class="dashboard-container">
    <aside class="sidebar">
      <h2>Employer Panel</h2>
      <ul>
        <li>Dashboard</li>
        <li>Post a Job</li>
        <li>Manage Listings</li>
        <li>Applicants</li>
        <li>Profile</li>
        <li>Logout</li>
      </ul>
    </aside>
    <main class="main-content">
      <header>
        <h1>Welcome, Employer</h1>
      </header>

      <section class="cards">
        <div class="card">
          <h3>Jobs Posted</h3>
          <p><?= $jobs_posted ?></p>
        </div>
        <div class="card">
          <h3>Applications Received</h3>
          <p><?= $applications_received ?></p>
        </div>
        <div class="card">
          <h3>Shortlisted</h3>
          <p><?= $shortlisted ?></p>
        </div>
        <div class="card">
          <h3>Interviews Scheduled</h3>
          <p><?= $interviews_scheduled ?></p>
        </div>
      </section>

      <section class="table-section">
        <h2>Recent Applicants</h2>
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Position</th>
              <th>Status</th>
              <th>Date Applied</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = pg_fetch_assoc($recent_applicants)): ?>
              <tr>
                <td><?= htmlspecialchars($row['student_name']) ?></td>
                <td><?= htmlspecialchars($row['position']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= date("d/m/Y", strtotime($row['application_date'])) ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </section>
    </main>
  </div>
</body>
</html>
