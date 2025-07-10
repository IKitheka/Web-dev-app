<?php
session_start();
require_once '../database/connection.php';
require_once '../database/auth_helpers.php';

require_auth('student');
$conn = create_connection();
$user_id = get_current_user_id();

$results_sql = "
    SELECT r.result_id, r.rating, r.completion_date, a.application_id,
           i.title as internship_title, e.company_name, c.certificate_id
    FROM \"Results\" r
    JOIN \"Applications\" a ON r.application_id = a.application_id
    JOIN \"Internships\" i ON a.internship_id = i.internship_id
    JOIN \"Employers\" e ON i.employer_id = e.employer_id
    LEFT JOIN \"Certificates\" c ON a.application_id = c.application_id
    WHERE a.student_id = $1
    ORDER BY r.completion_date DESC
";
$results = pg_query_params($conn, $results_sql, [$user_id]);

$completed_internships = ($results && pg_num_rows($results) > 0) ? pg_num_rows($results) : 0;

$cert_sql = "
    SELECT c.certificate_id, c.certificate_url, c.issue_date, c.verification_code, i.title as internship_title
    FROM \"Certificates\" c
    JOIN \"Applications\" a ON c.application_id = a.application_id
    JOIN \"Internships\" i ON a.internship_id = i.internship_id
    WHERE a.student_id = $1
    ORDER BY c.issue_date DESC
";
$certs = pg_query_params($conn, $cert_sql, [$user_id]);

$active_applications_query = "SELECT COUNT(*) FROM \"Applications\" WHERE student_id = $1 AND status = 'Pending'";
$active_applications_result = pg_query_params($conn, $active_applications_query, array($user_id));
$active_applications = pg_fetch_result($active_applications_result, 0, 0);

$recent_applications_query = "
    SELECT e.company_name, i.title AS position, a.application_date, a.status
    FROM \"Applications\" a
    JOIN \"Internships\" i ON a.internship_id = i.internship_id
    JOIN \"Employers\" e ON i.employer_id = e.employer_id
    WHERE a.student_id = $1
    ORDER BY a.application_date DESC
    LIMIT 5
";
$recent_applications_result = pg_query_params($conn, $recent_applications_query, array($user_id));

pg_close($conn);
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
            STUDENT DASHBOARD
        </header>
        
        <?php require_once '../includes/navigation.php'; echo render_navigation('dashboard'); ?>
        
        <div class="content">
            <h1 class="welcome">Welcome, Student</h1>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $active_applications; ?></div>
                    <div class="stat-label">Active Applications</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $completed_internships; ?></div>
                    <div class="stat-label">Completed Internships</div>
                </div>
            </div>
            
            <section style="margin-bottom:2rem;">
                <h2 style="color:white;">Completed Internships & Results</h2>
                <?php if ($results && pg_num_rows($results) > 0): ?>
                    <div class="cards">
                        <?php while ($row = pg_fetch_assoc($results)): ?>
                            <div class="card card-internship" style="margin-bottom:1rem; display: flex; flex-direction: column; align-items: flex-start; gap: 0.5rem;">
                                <div><strong><?php echo htmlspecialchars($row['internship_title']); ?></strong> at <?php echo htmlspecialchars($row['company_name']); ?></div>
                                <div>Completed: <?php echo date('M j, Y', strtotime($row['completion_date'])); ?></div>
                                <div>Rating: <?php echo $row['rating']; ?>/5</div>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.5rem;">
                                    <a href="../Results/view_results.php?application_id=<?php echo $row['application_id']; ?>" class="btn">View Result</a>
                                    <?php if ($row['certificate_id']): ?>
                                        <a href="../Certificates/download_certificate.php?certificate_id=<?php echo $row['certificate_id']; ?>" class="btn">Download Certificate</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div style="color:white;opacity:0.7;">No completed internships yet.</div>
                <?php endif; ?>
            </section>
            <section>
                <h2 style="color:white;">Certificates</h2>
                <?php if ($certs && pg_num_rows($certs) > 0): ?>
                    <div class="cards">
                        <?php while ($cert = pg_fetch_assoc($certs)): ?>
                            <div class="card card-certificate" style="margin-bottom:1rem; display: flex; flex-direction: column; align-items: flex-start; gap: 0.5rem;">
                                <div><strong><?php echo htmlspecialchars($cert['internship_title']); ?></strong></div>
                                <div>Issued: <?php echo date('M j, Y', strtotime($cert['issue_date'])); ?></div>
                                <div>Verification Code: <span style="font-family:monospace; color:var(--color-cyan-300);"><?php echo htmlspecialchars($cert['verification_code']); ?></span></div>
                                <div style="margin-top: 0.5rem;">
                                    <a href="../Certificates/download_certificate.php?certificate_id=<?php echo $cert['certificate_id']; ?>" class="btn">Download Certificate</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div style="color:white;opacity:0.7;">No certificates issued yet.</div>
                <?php endif; ?>
            </section>
            
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
                        while ($row = pg_fetch_assoc($recent_applications_result)): 
                            $has_applications = true;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['position']); ?></td>
                                <td><?php echo date("d/m/Y", strtotime($row['application_date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
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