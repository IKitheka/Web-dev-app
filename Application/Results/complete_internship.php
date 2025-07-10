<?php
session_start();
require_once '../database/connection.php';
require_once '../database/auth_helpers.php';
require_auth();
$conn = create_connection();
$user_type = $_SESSION['user_type'];
$applications_sql = "";
if ($user_type === 'employer') {
    $employer_id = $_SESSION['user_id'];
    $applications_sql = "
        SELECT a.application_id, a.status, a.application_date,
               s.name as student_name, s.email as student_email,
               i.title as internship_title, i.end_date,
               e.company_name,
               r.result_id
        FROM \"Applications\" a
        JOIN \"Students\" s ON a.student_id = s.student_id
        JOIN \"Internships\" i ON a.internship_id = i.internship_id
        JOIN \"Employers\" e ON i.employer_id = e.employer_id
        LEFT JOIN \"Results\" r ON a.application_id = r.application_id
        WHERE i.employer_id = $1 AND a.status = 'Approved'
        ORDER BY i.end_date DESC
    ";
    $result = pg_query_params($conn, $applications_sql, [$employer_id]);
} else {
    $applications_sql = "
        SELECT a.application_id, a.status, a.application_date,
               s.name as student_name, s.email as student_email,
               i.title as internship_title, i.end_date,
               e.company_name,
               r.result_id
        FROM \"Applications\" a
        JOIN \"Students\" s ON a.student_id = s.student_id
        JOIN \"Internships\" i ON a.internship_id = i.internship_id
        JOIN \"Employers\" e ON i.employer_id = e.employer_id
        LEFT JOIN \"Results\" r ON a.application_id = r.application_id
        WHERE a.status = 'Approved'
        ORDER BY i.end_date DESC
    ";
    $result = pg_query($conn, $applications_sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Internships | Intern Connect</title>
    <link rel="icon" href="../static/images/title.png">
    <link rel="stylesheet" href="../static/css/index.css">
    <style>
        .completion-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .internship-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 15px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .internship-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }
        .card-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-completed {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        .status-active {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        .complete-btn {
            background: linear-gradient(135deg, var(--color-cyan-500), var(--color-cyan-400));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .complete-btn:hover {
            background: linear-gradient(135deg, var(--color-cyan-400), var(--color-cyan-300));
            transform: translateY(-1px);
        }
        .completed-badge {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            border: 1px solid rgba(34, 197, 94, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }
    </style>
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
    <header class="header">COMPLETE INTERNSHIPS</header>
    <?php require_once '../includes/navigation.php'; echo render_navigation('complete_internships'); ?>
    <div class="content">
        <h1 class="welcome">Approved Internships Ready for Completion</h1>
        <div class="completion-grid">
            <?php
            if ($result && pg_num_rows($result) > 0) {
                while ($row = pg_fetch_assoc($result)) {
                    $is_completed = !is_null($row['result_id']);
                    $end_date = new DateTime($row['end_date']);
                    $today = new DateTime();
                    $can_complete = $end_date <= $today;
                    echo '<div class="internship-card">';
                    echo '  <div class="card-header">';
                    echo '    <div>';
                    echo '      <h3 style="color: white; margin: 0 0 0.5rem 0;">' . htmlspecialchars($row['internship_title']) . '</h3>';
                    echo '      <p style="color: rgba(255,255,255,0.8); margin: 0;">Student: ' . htmlspecialchars($row['student_name']) . '</p>';
                    echo '      <p style="color: rgba(255,255,255,0.7); margin: 0; font-size: 0.9rem;">Company: ' . htmlspecialchars($row['company_name']) . '</p>';
                    echo '    </div>';
                    if ($is_completed) {
                        echo '    <span class="status-badge status-completed">✅ Completed</span>';
                    } else {
                        echo '    <span class="status-badge status-active">🔄 Active</span>';
                    }
                    echo '  </div>';
                    echo '  <div style="margin-bottom: 1rem;">';
                    echo '    <div style="color: rgba(255,255,255,0.7); font-size: 0.8rem; margin-bottom: 0.25rem;">END DATE</div>';
                    echo '    <div style="color: white; font-weight: 500;">' . $end_date->format('F j, Y') . '</div>';
                    echo '  </div>';
                    echo '  <div style="color: rgba(255,255,255,0.7); font-size: 0.8rem; margin-bottom: 0.25rem;">STUDENT EMAIL</div>';
                    echo '  <div style="color: white; font-weight: 500; margin-bottom: 1rem;">' . htmlspecialchars($row['student_email']) . '</div>';
                    if ($is_completed) {
                        echo '  <div class="completed-badge">✅ Feedback Submitted</div>';
                        echo '  <a href="view_results.php?application_id=' . htmlspecialchars($row['application_id']) . '" class="complete-btn" style="background: linear-gradient(135deg, var(--color-purple-900), var(--color-indigo-800)); margin-left: 1rem;">👁️ View Results</a>';
                    } else if ($can_complete) {
                        echo '  <a href="feedback_form.php?application_id=' . htmlspecialchars($row['application_id']) . '" class="complete-btn">📝 Complete & Add Feedback</a>';
                    } else {
                        echo '  <div style="color: rgba(255,255,255,0.6); font-style: italic; margin-top: 1rem;">⏳ Internship ends on ' . $end_date->format('M j, Y') . '</div>';
                    }
                    echo '</div>';
                }
            } else {
                echo '<div style="grid-column: 1 / -1; text-align: center; padding: 3rem; background: rgba(255,255,255,0.05); border: 2px dashed rgba(255,255,255,0.2); border-radius: 15px; color: rgba(255,255,255,0.7);">';
                echo '  <h3>📋 No Approved Internships Found</h3>';
                echo '  <p>There are currently no approved internships ready for completion.</p>';
                echo '</div>';
            }
            pg_close($conn);
            ?>
        </div>
    </div>
    <footer class="footer">Intern Connect &copy; 2025 | All Rights Reserved</footer>
</body>
</html>
