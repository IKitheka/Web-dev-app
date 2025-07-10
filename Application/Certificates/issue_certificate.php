<?php
require_once '../database/connection.php';
require_once '../database/auth_helpers.php';

// Check authentication - admins only
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../Authentication/login.php');
    exit();
}

$conn = create_connection();
$application_id = $_GET['application_id'] ?? null;

if (!$application_id) {
    header('Location: certificate_list.php');
    exit();
}

// Get application and result details
$application_sql = "
    SELECT a.application_id, a.status, a.application_date,
           s.student_id, s.name as student_name, s.email as student_email,
           i.internship_id, i.title as internship_title, i.start_date, i.end_date, i.description,
           e.employer_id, e.company_name, e.industry,
           r.result_id, r.employer_feedback, r.rating, r.completion_date,
           c.certificate_id
    FROM \"Applications\" a
    JOIN \"Students\" s ON a.student_id = s.student_id
    JOIN \"Internships\" i ON a.internship_id = i.internship_id
    JOIN \"Employers\" e ON i.employer_id = e.employer_id
    LEFT JOIN \"Results\" r ON a.application_id = r.application_id
    LEFT JOIN \"Certificates\" c ON a.application_id = c.application_id
    WHERE a.application_id = $1
";

$result = pg_query_params($conn, $application_sql, [$application_id]);

if (!$result || pg_num_rows($result) === 0) {
    $_SESSION['error_message'] = 'Application not found.';
    header('Location: certificate_list.php');
    exit();
}

$data = pg_fetch_assoc($result);

// Check if result exists (internship must be completed)
if (!$data['result_id']) {
    $_SESSION['error_message'] = 'Cannot issue certificate - internship not completed yet.';
    header('Location: ../Results/complete_internship.php');
    exit();
}

// Check if certificate already exists
if ($data['certificate_id']) {
    $_SESSION['info_message'] = 'Certificate already issued for this internship.';
    header('Location: certificate_list.php');
    exit();
}

$success_message = '';
$error_message = '';

if ($_POST) {
    $certificate_type = $_POST['certificate_type'] ?? 'completion';
    $issue_date = $_POST['issue_date'] ?? date('Y-m-d');
    $custom_notes = trim($_POST['custom_notes'] ?? '');
    $admin_id = $_SESSION['user_id'];
    
    // Generate unique verification code
    $verification_code = 'CERT-' . date('Y') . '-' . sprintf('%03d', rand(1, 999)) . '-' . 
                        strtoupper(substr($data['student_name'], 0, 2)) . '-' . 
                        strtoupper(substr($data['company_name'], 0, 2));
    
    // Generate certificate URL (this would normally be a file path after PDF generation)
    $certificate_filename = strtolower(str_replace(' ', '_', $data['student_name'])) . '_' . 
                           strtolower(str_replace(' ', '_', $data['company_name'])) . '_cert.pdf';
    $certificate_url = 'https://certificates.strathmore.edu/' . date('Y') . '/' . $certificate_filename;
    
    try {
        // Insert certificate record
        $insert_sql = "
            INSERT INTO \"Certificates\" (application_id, admin_id, issue_date, certificate_url, verification_code)
            VALUES ($1, $2, $3, $4, $5)
        ";
        
        $insert_result = pg_query_params($conn, $insert_sql, [
            $application_id,
            $admin_id,
            $issue_date,
            $certificate_url,
            $verification_code
        ]);
        
        if ($insert_result) {
            $_SESSION['success_message'] = 'Certificate issued successfully! Verification Code: ' . $verification_code;
            header('Location: certificate_list.php');
            exit();
        } else {
            $error_message = 'Failed to issue certificate. Please try again.';
        }
    } catch (Exception $e) {
        $error_message = 'Error issuing certificate: ' . $e->getMessage();
    }
}

// Generate star display for rating
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Certificate | Intern Connect</title>
    <link rel="icon" href="../static/images/title.png">
    <link rel="stylesheet" href="../static/css/index.css">
    <style>
        .issue-container {
            max-width: 800px;
            margin: 2rem auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .completion-summary {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .summary-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .rating-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .certificate-preview {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(59, 130, 246, 0.1));
            border: 2px solid rgba(139, 92, 246, 0.3);
            border-radius: 12px;
            padding: 2rem;
            margin: 2rem 0;
            text-align: center;
            position: relative;
        }

        .cert-seal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 60px;
            height: 60px;
            background: radial-gradient(circle, #fbbf24, #f59e0b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            font-weight: bold;
        }

        .cert-title {
            color: var(--color-cyan-300);
            font-size: 1.8rem;
            font-weight: bold;
            margin: 1rem 0;
        }

        .cert-content {
            color: white;
            line-height: 1.6;
            margin: 1rem 0;
        }

        .form-section {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .issue-btn {
            background: linear-gradient(135deg, var(--color-purple-900), var(--color-indigo-800));
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .issue-btn:hover {
            background: linear-gradient(135deg, var(--color-indigo-800), var(--color-purple-900));
            transform: translateY(-1px);
        }

        .cancel-btn {
            background: rgba(255, 255, 255, 0.2);
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
        }

        .cancel-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .verification-preview {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
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
    
    <header class="header">ISSUE CERTIFICATE</header>
    
    <nav class="nav">
        <a href="../Dashboards/admin_dashboard.php">Dashboard</a>
        <a href="../Results/complete_internship.php">Complete Internships</a>
        <a href="../Certificates/certificate_list.php">Certificates</a>
        <a href="../logout.php" style="margin-left: auto;">Log out</a>
    </nav>

    <div class="content">
        <div class="issue-container">
            <h1 style="color: white; margin-bottom: 2rem; text-align: center;">Issue Internship Certificate</h1>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <!-- Completion Summary -->
            <div class="completion-summary">
                <h2 style="color: white; margin: 0 0 1.5rem 0;">Internship Completion Summary</h2>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 0.5rem;">Student</div>
                        <div style="color: white; font-weight: 500; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($data['student_name']); ?></div>
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.9rem;"><?php echo htmlspecialchars($data['student_email']); ?></div>
                    </div>
                    
                    <div class="summary-item">
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 0.5rem;">Position</div>
                        <div style="color: white; font-weight: 500; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($data['internship_title']); ?></div>
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.9rem;"><?php echo htmlspecialchars($data['company_name']); ?></div>
                    </div>
                    
                    <div class="summary-item">
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 0.5rem;">Duration</div>
                        <div style="color: white; font-weight: 500; margin-bottom: 0.25rem;">
                            <?php echo date('M j, Y', strtotime($data['start_date'])); ?> - 
                            <?php echo date('M j, Y', strtotime($data['end_date'])); ?>
                        </div>
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">
                            Completed: <?php echo date('M j, Y', strtotime($data['completion_date'])); ?>
                        </div>
                    </div>
                    
                    <div class="summary-item">
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 0.5rem;">Performance Rating</div>
                        <div class="rating-display">
                            <div style="font-size: 1.2rem;"><?php echo generateStars($data['rating']); ?></div>
                            <span style="color: var(--color-cyan-300); font-weight: 600;"><?php echo $data['rating']; ?>/5</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Certificate Preview -->
            <div class="certificate-preview">
                <div class="cert-seal">🏆</div>
                <div class="cert-title">CERTIFICATE OF COMPLETION</div>
                <div class="cert-content">
                    <p>This is to certify that</p>
                    <h3 style="color: var(--color-cyan-300); margin: 1rem 0; font-size: 1.5rem;"><?php echo htmlspecialchars($data['student_name']); ?></h3>
                    <p>has successfully completed the internship program</p>
                    <h4 style="color: white; margin: 1rem 0;"><?php echo htmlspecialchars($data['internship_title']); ?></h4>
                    <p>at <strong><?php echo htmlspecialchars($data['company_name']); ?></strong></p>
                    <p style="margin-top: 1.5rem;">Period: <?php echo date('F j, Y', strtotime($data['start_date'])); ?> to <?php echo date('F j, Y', strtotime($data['end_date'])); ?></p>
                </div>
            </div>

            <!-- Issue Form -->
            <div class="form-section">
                <h3 style="color: white; margin: 0 0 1.5rem 0;">Certificate Details</h3>
                
                <form method="POST" action="">
                    <div style="margin-bottom: 1.5rem;">
                        <label for="certificate_type" style="color: white; font-weight: 500; display: block; margin-bottom: 0.5rem;">
                            Certificate Type
                        </label>
                        <select name="certificate_type" id="certificate_type" class="form-input" style="width: 100%;">
                            <option value="completion">Completion Certificate</option>
                            <option value="achievement">Achievement Certificate</option>
                            <option value="excellence">Excellence Certificate</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="issue_date" style="color: white; font-weight: 500; display: block; margin-bottom: 0.5rem;">
                            Issue Date
                        </label>
                        <input 
                            type="date" 
                            name="issue_date" 
                            id="issue_date"
                            class="form-input"
                            style="width: auto; padding: 0.75rem;"
                            value="<?php echo date('Y-m-d'); ?>"
                            max="<?php echo date('Y-m-d'); ?>"
                            required
                        >
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="custom_notes" style="color: white; font-weight: 500; display: block; margin-bottom: 0.5rem;">
                            Additional Notes (Optional)
                        </label>
                        <textarea 
                            name="custom_notes" 
                            id="custom_notes" 
                            class="form-textarea"
                            style="width: 100%; min-height: 80px;"
                            placeholder="Any special recognition or additional comments..."
                        ></textarea>
                    </div>

                    <div class="verification-preview">
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.9rem; margin-bottom: 0.5rem;">
                            Verification Code Format: <strong>CERT-YYYY-XXX-AA-BB</strong>
                        </div>
                        <div style="color: rgba(255,255,255,0.6); font-size: 0.8rem;">
                            A unique verification code will be automatically generated for this certificate
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="certificate_list.php" class="cancel-btn">Cancel</a>
                        <button type="submit" class="issue-btn">🏆 Issue Certificate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="footer">Intern Connect &copy; 2025 | All Rights Reserved</footer>

    <script>
        // Update certificate preview based on type selection
        document.getElementById('certificate_type').addEventListener('change', function() {
            const certTitle = document.querySelector('.cert-title');
            const type = this.value;
            
            switch(type) {
                case 'achievement':
                    certTitle.textContent = 'CERTIFICATE OF ACHIEVEMENT';
                    break;
                case 'excellence':
                    certTitle.textContent = 'CERTIFICATE OF EXCELLENCE';
                    break;
                default:
                    certTitle.textContent = 'CERTIFICATE OF COMPLETION';
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const issueDate = new Date(document.getElementById('issue_date').value);
            const today = new Date();
            
            if (issueDate > today) {
                e.preventDefault();
                alert('Issue date cannot be in the future.');
                return false;
            }
            
            return confirm('Are you sure you want to issue this certificate? This action cannot be undone.');
        });
    </script>
</body>
</html>
