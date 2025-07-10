<?php
session_start();
require_once '../database/connection.php';
require_once '../database/auth_helpers.php';
require_auth();

$conn = create_connection();
$application_id = $_GET['application_id'] ?? null;

if (!$application_id) {
    header('Location: ../Dashboards/' . $_SESSION['user_type'] . '_dashboard.php');
    exit();
}

// Get result details with application info
$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

$result_sql = "
    SELECT r.result_id, r.employer_feedback, r.student_feedback, r.rating, r.completion_date, r.created_at,
           a.application_id, a.status, a.application_date,
           s.student_id, s.name as student_name, s.email as student_email,
           i.internship_id, i.title as internship_title, i.start_date, i.end_date, i.description,
           e.employer_id, e.company_name, e.industry,
           c.certificate_id, c.certificate_url, c.verification_code, c.issue_date
    FROM \"Results\" r
    JOIN \"Applications\" a ON r.application_id = a.application_id
    JOIN \"Students\" s ON a.student_id = s.student_id
    JOIN \"Internships\" i ON a.internship_id = i.internship_id
    JOIN \"Employers\" e ON i.employer_id = e.employer_id
    LEFT JOIN \"Certificates\" c ON a.application_id = c.application_id
    WHERE a.application_id = $1
";

$result = pg_query_params($conn, $result_sql, [$application_id]);

if (!$result || pg_num_rows($result) === 0) {
    $_SESSION['error_message'] = 'Result not found or access denied.';
    header('Location: ../Dashboards/' . $_SESSION['user_type'] . '_dashboard.php');
    exit();
}

$data = pg_fetch_assoc($result);

// Check access permissions
$has_access = false;
if ($user_type === 'admin') {
    $has_access = true;
} elseif ($user_type === 'student' && $data['student_id'] === $user_id) {
    $has_access = true;
} elseif ($user_type === 'employer' && $data['employer_id'] === $user_id) {
    $has_access = true;
}

if (!$has_access) {
    $_SESSION['error_message'] = 'Access denied to this result.';
    header('Location: ../Dashboards/' . $_SESSION['user_type'] . '_dashboard.php');
    exit();
}

// Generate star display
function generateStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<span style="color: #fbbf24;">★</span>';
        } else {
            $stars .= '<span style="color: rgba(255,255,255,0.3);">★</span>';
        }
    }
    return $stars;
}

function getRatingText($rating) {
    $labels = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
    return $labels[$rating] ?? 'Unknown';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Results | Intern Connect</title>
    <link rel="icon" href="../static/images/title.png">
    <link rel="stylesheet" href="../static/css/index.css">
    <style>
        .results-container {
            max-width: 900px;
            margin: 2rem auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .result-header {
            text-align: center;
            padding-bottom: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }

        .completion-badge {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .internship-overview {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }

        .overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .overview-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .feedback-section {
            background: rgba(255, 255, 255, 0.05);
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }

        .rating-display {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
        }

        .rating-stars {
            font-size: 1.5rem;
            display: flex;
            gap: 0.25rem;
        }

        .rating-text {
            color: var(--color-cyan-300);
            font-weight: 600;
            font-size: 1.1rem;
        }

        .feedback-text {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
            font-size: 1rem;
        }

        .certificate-section {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(59, 130, 246, 0.2));
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid rgba(139, 92, 246, 0.3);
            text-align: center;
            margin-bottom: 2rem;
        }

        .certificate-available {
            color: #22c55e;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .certificate-pending {
            color: #fbbf24;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .download-btn {
            background: linear-gradient(135deg, var(--color-purple-900), var(--color-indigo-800));
            color: white;
            border: none;
            padding: 0.75rem 2rem;
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

        .download-btn:hover {
            background: linear-gradient(135deg, var(--color-indigo-800), var(--color-purple-900));
            transform: translateY(-1px);
        }

        .back-btn {
            background: rgba(255, 255, 255, 0.2);
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
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .student-feedback-section {
            background: rgba(255, 255, 255, 0.03);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            margin-top: 2rem;
        }

        .add-student-feedback-btn {
            background: linear-gradient(135deg, var(--color-cyan-500), var(--color-cyan-400));
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .verification-code {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-family: monospace;
            font-weight: 600;
            color: var(--color-cyan-300);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-top: 0.5rem;
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
    
    <header class="header">INTERNSHIP RESULTS</header>
    
    <?php
        require_once '../includes/navigation.php';
        if ($user_type === 'admin' || $user_type === 'employer') {
            echo render_navigation('complete_internships');
        } else {
            echo render_navigation('my_applications');
        }
    ?>

    <div class="content">
        <div class="results-container">
            <!-- Result Header -->
            <div class="result-header">
                <div class="completion-badge">
                    ✅ Internship Completed
                </div>
                <h1 style="color: white; margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($data['internship_title']); ?></h1>
                <p style="color: rgba(255,255,255,0.8); margin: 0; font-size: 1.1rem;">
                    Completed on <?php echo date('F j, Y', strtotime($data['completion_date'])); ?>
                </p>
            </div>

            <!-- Internship Overview -->
            <div class="internship-overview">
                <h2 style="color: white; margin: 0 0 1.5rem 0;">Internship Overview</h2>
                <div class="overview-grid">
                    <div class="overview-item">
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 0.5rem;">Student</div>
                        <div style="color: white; font-weight: 500;"><?php echo htmlspecialchars($data['student_name']); ?></div>
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.9rem;"><?php echo htmlspecialchars($data['student_email']); ?></div>
                    </div>
                    
                    <div class="overview-item">
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 0.5rem;">Company</div>
                        <div style="color: white; font-weight: 500;"><?php echo htmlspecialchars($data['company_name']); ?></div>
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.9rem;"><?php echo htmlspecialchars($data['industry']); ?></div>
                    </div>
                    
                    <div class="overview-item">
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 0.5rem;">Duration</div>
                        <div style="color: white; font-weight: 500;">
                            <?php echo date('M j, Y', strtotime($data['start_date'])); ?> - 
                            <?php echo date('M j, Y', strtotime($data['end_date'])); ?>
                        </div>
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">
                            <?php 
                            $start = new DateTime($data['start_date']);
                            $end = new DateTime($data['end_date']);
                            $interval = $start->diff($end);
                            echo $interval->format('%m months, %d days');
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employer Feedback Section -->
            <div class="feedback-section">
                <h2 style="color: white; margin: 0 0 1.5rem 0;">Employer Evaluation</h2>
                
                <div class="rating-display">
                    <div>
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.9rem; margin-bottom: 0.5rem;">Overall Rating</div>
                        <div class="rating-stars"><?php echo generateStars($data['rating']); ?></div>
                    </div>
                    <div class="rating-text">
                        <?php echo $data['rating']; ?>/5 - <?php echo getRatingText($data['rating']); ?>
                    </div>
                </div>

                <div>
                    <div style="color: rgba(255,255,255,0.7); font-size: 0.9rem; margin-bottom: 1rem;">Detailed Feedback</div>
                    <div class="feedback-text">
                        <?php echo nl2br(htmlspecialchars($data['employer_feedback'])); ?>
                    </div>
                </div>
            </div>

            <!-- Student Feedback Section -->
            <?php if (!empty($data['student_feedback'])): ?>
                <div class="student-feedback-section">
                    <h3 style="color: white; margin: 0 0 1rem 0;">Student Experience Feedback</h3>
                    <div class="feedback-text">
                        <?php echo nl2br(htmlspecialchars($data['student_feedback'])); ?>
                    </div>
                </div>
            <?php elseif ($user_type === 'student'): ?>
                <div class="student-feedback-section">
                    <h3 style="color: white; margin: 0 0 1rem 0;">Your Experience Feedback</h3>
                    <p style="color: rgba(255,255,255,0.7); margin-bottom: 1rem;">
                        Share your experience about this internship to help future students and improve the program.
                    </p>
                    <a href="student_feedback_form.php?application_id=<?php echo $application_id; ?>" class="add-student-feedback-btn">
                        📝 Add Your Feedback
                    </a>
                </div>
            <?php endif; ?>

            <!-- Certificate Section -->
            <div class="certificate-section">
                <?php if ($data['certificate_id']): ?>
                    <div class="certificate-available">
                        🏆 Certificate Available
                    </div>
                    <h3 style="color: white; margin: 0 0 1rem 0;">Internship Completion Certificate</h3>
                    <p style="color: rgba(255,255,255,0.8); margin-bottom: 1rem;">
                        Your certificate has been issued on <?php echo date('F j, Y', strtotime($data['issue_date'])); ?>
                    </p>
                    <div class="verification-code">
                        Verification Code: <?php echo htmlspecialchars($data['verification_code']); ?>
                    </div>
                    <a href="../Certificates/download_certificate.php?certificate_id=<?php echo $data['certificate_id']; ?>" class="download-btn" target="_blank">
                        📄 Download Certificate
                    </a>
                <?php else: ?>
                    <div class="certificate-pending">
                        ⏳ Certificate Pending
                    </div>
                    <h3 style="color: white; margin: 0 0 1rem 0;">Certificate Status</h3>
                    <p style="color: rgba(255,255,255,0.7);">
                        Your completion certificate is being processed by the administration team. 
                        You will be notified once it's ready for download.
                    </p>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div style="text-align: center;">
                <?php if ($user_type === 'admin'): ?>
                    <a href="../Results/complete_internship.php" class="back-btn">← Back to Completions</a>
                    <?php if (!$data['certificate_id']): ?>
                        <a href="../Certificates/issue_certificate.php?application_id=<?php echo $application_id; ?>" class="download-btn" style="margin-left: 1rem;">
                            🏆 Issue Certificate
                        </a>
                    <?php endif; ?>
                <?php elseif ($user_type === 'employer'): ?>
                    <a href="../Results/complete_internship.php" class="back-btn">← Back to Completions</a>
                <?php else: ?>
                    <a href="../Forms/my_applications.php" class="back-btn">← Back to My Applications</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">Intern Connect &copy; 2025 | All Rights Reserved</footer>
</body>
</html>
