<?php
session_start();
include '../database/connection.php';
$conn = create_connection();

$student_id = $_SESSION['student_id'];

if (!$student_id) {
    header('Location: ../process_login.php');
    exit();
}

// Get counts
$active_applications = pg_fetch_result(pg_query($conn, "
  SELECT COUNT(*) FROM Applications 
  WHERE student_id = '$student_id' AND status = 'Pending'
"), 0, 0);

$completed_internships = pg_fetch_result(pg_query($conn, "
  SELECT COUNT(*) FROM Applications a
  JOIN Results r ON a.application_id = r.application_id
  WHERE a.student_id = '$student_id' AND r.completion_date IS NOT NULL
"), 0, 0);

$pending_applications = pg_fetch_result(pg_query($conn, "
  SELECT COUNT(*) FROM Applications 
  WHERE student_id = '$student_id' AND status = 'Pending'
"), 0, 0);

// Get recent applications
$recent_applications = pg_query($conn, "
  SELECT e.company_name, i.title AS position, a.application_date, a.status
  FROM Applications a
  JOIN Internships i ON a.internship_id = i.internship_id
  JOIN Employers e ON i.employer_id = e.employer_id
  WHERE a.student_id = '$student_id'
  ORDER BY a.application_date DESC
  LIMIT 5
");
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Intern Connect | Student Dashboard</title>
        <link rel="icon" type="image/x-icon" href="/static/images/title.png">
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
        
        <header class="header">
            STUDENT DASHBOARD
        </header>
        
        <nav class="nav">
            <a href="#">INTERN CONNECT</a>
            <a href="#">Dashboard</a>
            <a href="#">Browse Internships</a>
            <a href="#">My Applications</a>
            <a href="#">Profile</a>
            <a href="#" style="margin-left: auto;">Log out</a>
        </nav>
        
        <div class="content">
            <h1 class="welcome">Welcome, User</h1>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number"><?= $active_applications ?></div>
                    <div class="stat-label">Active Applications</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?= $completed_internships ?></div>
                    <div class="stat-label">Completed Internships</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?= $pending_applications ?></div>
                    <div class="stat-label">Pending Applications</div>
                </div>
            </div>
            
            <div class="recent-apps">
                <h2>Recent Applications</h2>
                <table class="apps-table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Position</th>
                            <th>Date Applied</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $has_applications = false;
                        while ($row = pg_fetch_assoc($recent_applications)): 
                            $has_applications = true;
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($row['company_name']) ?></td>
                                <td><?= htmlspecialchars($row['position']) ?></td>
                                <td><?= date("d/m/Y", strtotime($row['application_date'])) ?></td>
                                <td><?= htmlspecialchars($row['status']) ?></td>
                                <td><button class="btn">View</button></td>
                            </tr>
                        <?php endwhile; ?>
                        
                        <?php if (!$has_applications): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px;">
                                    No recent applications found. Start applying to internships!
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <footer class="footer">
            Intern Connect &copy; 2025 | All Rights Reserved
        </footer>
    </body>
</html>