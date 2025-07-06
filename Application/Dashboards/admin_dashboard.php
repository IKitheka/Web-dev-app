<?php
session_start();
include '../database/connection.php';
$conn = create_connection(); 

// Fetch total stats
$employer_count = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM Employers"), 0, 0);
$student_count = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM Students"), 0, 0);
$active_jobs = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM Internships WHERE is_active = TRUE"), 0, 0);

// Fetch recent employers
$recent_employers = pg_query($conn, "SELECT company_name, email, created_at FROM Employers ORDER BY created_at DESC LIMIT 4");

// Fetch recent students
$recent_students = pg_query($conn, "SELECT name, email, phone, department, academic_year, created_at FROM Students ORDER BY created_at DESC LIMIT 3");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Intern Connect | Admin Dashboard</title>
  <link rel="icon" type="image/x-icon" href="/static/images/title.png">
  <link rel="stylesheet" href="../static/index.css">
</head>
<body>
  <!-- Aurora Background Animation -->
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

  <!-- Header -->
  <header class="header">
    ADMIN DASHBOARD
  </header>

  <!-- Navigation -->
  <nav class="nav">
    <a href="#">INTERN CONNECT</a>
    <a href="#">Dashboard</a>
    <a href="#">Employers</a>
    <a href="#">Students</a>
    <a href="#">Profile</a>
    <a href="#" style="margin-left: auto;">Log out</a>
  </nav>

  <!-- Main Content -->
  <div class="content">
    <h1 class="welcome">Welcome, Admin</h1>

    <!-- Stats -->
    <div class="stats-container">
      <div class="stat-card">
        <div class="stat-number"><?= $employer_count ?></div>
        <div class="stat-label">Total Employers</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?= $student_count ?></div>
        <div class="stat-label">Total Job Seekers</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?= $active_jobs ?></div>
        <div class="stat-label">Active Internships</div>
      </div>
      <div class="stat-card">
        <div class="stat-number">—</div>
        <div class="stat-label">Pending Reviews</div> <!-- You can replace this with logic if needed -->
      </div>
    </div>

    <!-- Recent Employers -->
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
          <?php while ($row = pg_fetch_assoc($recent_employers)): ?>
            <tr>
              <td><?= htmlspecialchars($row['company_name']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= date("d/m/Y", strtotime($row['created_at'])) ?></td>
              <td><button class="btn">View</button></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- Recent Students -->
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
          <?php while ($row = pg_fetch_assoc($recent_students)): ?>
            <tr>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= htmlspecialchars($row['phone']) ?></td>
              <td><?= htmlspecialchars($row['department']) ?></td>
              <td><?= htmlspecialchars($row['academic_year']) ?></td>
              <td><?= date("d/m/Y", strtotime($row['created_at'])) ?></td>
              <td><button class="btn">View</button></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Footer -->
  <footer class="footer">
    Intern Connect &copy; 2025 | All Rights Reserved
  </footer>
</body>
</html>
