<?php
session_start();
require_once '../database/connection.php';
require_once '../database/auth_helpers.php';

// Check authentication - allow students, employers, and admins
if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['student', 'employer', 'admin'])) {
    header('Location: ../Authentication/login.php');
    exit();
}

$conn = create_connection();
$certificate_id = $_GET['certificate_id'] ?? null;

if (!$certificate_id) {
    $_SESSION['error_message'] = 'Certificate ID is required.';
    header('Location: ../Dashboards/' . $_SESSION['user_type'] . '_dashboard.php');
    exit();
}

// Get certificate details with access control
$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

$certificate_sql = "
    SELECT c.certificate_id, c.issue_date, c.certificate_url, c.verification_code, c.created_at,
           a.application_id,
           s.student_id, s.name as student_name, s.email as student_email,
           i.title as internship_title, i.start_date, i.end_date, i.description,
           e.employer_id, e.company_name, e.industry, e.location,
           r.rating, r.completion_date, r.employer_feedback,
           admin.full_name as issued_by
    FROM \"Certificates\" c
    JOIN \"Applications\" a ON c.application_id = a.application_id
    JOIN \"Students\" s ON a.student_id = s.student_id
    JOIN \"Internships\" i ON a.internship_id = i.internship_id
    JOIN \"Employers\" e ON i.employer_id = e.employer_id
    JOIN \"Results\" r ON a.application_id = r.application_id
    LEFT JOIN \"Administrators\" admin ON c.admin_id = admin.admin_id
    WHERE c.certificate_id = $1
";

$result = pg_query_params($conn, $certificate_sql, [$certificate_id]);

if (!$result || pg_num_rows($result) === 0) {
    $_SESSION['error_message'] = 'Certificate not found.';
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
    $_SESSION['error_message'] = 'Access denied to this certificate.';
    header('Location: ../Dashboards/' . $_SESSION['user_type'] . '_dashboard.php');
    exit();
}

// Check if this is a download request or preview
$action = $_GET['action'] ?? 'preview';

if ($action === 'download') {
    // Generate PDF certificate
    generateAndDownloadCertificate($data);
    exit();
}

// Helper function to generate stars
function generateStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '★';
        } else {
            $stars .= '☆';
        }
    }
    return $stars;
}

// Function to generate and download certificate (simplified version)
function generateAndDownloadCertificate($data) {
    // In a real implementation, you would generate a PDF here
    // For now, we'll create a simple HTML certificate that can be printed/saved as PDF
    
    $certificate_html = generateCertificateHTML($data);
    
    // Set headers for download
    $filename = 'certificate_' . strtolower(str_replace(' ', '_', $data['student_name'])) . '_' . date('Y-m-d') . '.html';
    
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo $certificate_html;
}

function generateCertificateHTML($data) {
    $duration = calculateDuration($data['start_date'], $data['end_date']);
    
    return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Completion - ' . htmlspecialchars($data['student_name']) . '</title>
    <style>
        @page { size: A4 landscape; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Times New Roman", serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 40px;
            line-height: 1.6;
        }
        .certificate {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border: 8px solid #1e40af;
            border-radius: 20px;
            padding: 60px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            position: relative;
            min-height: 600px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .certificate::before {
            content: "";
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            border: 2px solid #3b82f6;
            border-radius: 12px;
        }
        .header {
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }
        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
        }
        .title {
            font-size: 3rem;
            color: #1e40af;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 10px;
        }
        .subtitle {
            font-size: 1.2rem;
            color: #64748b;
            font-style: italic;
        }
        .content {
            margin: 40px 0;
            position: relative;
            z-index: 1;
        }
        .recipient-name {
            font-size: 2.5rem;
            color: #1e40af;
            font-weight: bold;
            margin: 20px 0;
            text-decoration: underline;
            text-decoration-color: #3b82f6;
        }
        .achievement-text {
            font-size: 1.3rem;
            color: #374151;
            margin: 20px 0;
            line-height: 1.8;
        }
        .program-name {
            font-size: 1.8rem;
            color: #1e40af;
            font-weight: bold;
            margin: 15px 0;
        }
        .company-name {
            font-size: 1.4rem;
            color: #059669;
            font-weight: 600;
            margin: 10px 0;
        }
        .details {
            margin: 30px 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            text-align: left;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        .detail-item {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }
        .detail-label {
            font-size: 0.9rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .detail-value {
            font-size: 1.1rem;
            color: #1e293b;
            font-weight: 500;
        }
        .footer {
            margin-top: 40px;
            position: relative;
            z-index: 1;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        .signature {
            text-align: center;
            flex: 1;
        }
        .signature-line {
            border-top: 2px solid #1e40af;
            width: 150px;
            margin: 0 auto 10px;
        }
        .signature-title {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 600;
        }
        .verification {
            margin-top: 30px;
            padding: 15px;
            background: #f1f5f9;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .verification-code {
            font-family: monospace;
            font-size: 1rem;
            color: #1e40af;
            font-weight: bold;
            margin-top: 5px;
        }
        .rating-stars {
            font-size: 1.5rem;
            color: #fbbf24;
            margin: 10px 0;
        }
        @media print {
            body { background: white; padding: 0; }
            .certificate { border: 8px solid #1e40af; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="header">
            <div class="logo">IC</div>
            <div class="title">Certificate</div>
            <div class="subtitle">of Internship Completion</div>
        </div>
        
        <div class="content">
            <div class="achievement-text">This is to certify that</div>
            <div class="recipient-name">' . htmlspecialchars($data['student_name']) . '</div>
            <div class="achievement-text">has successfully completed the internship program</div>
            <div class="program-name">' . htmlspecialchars($data['internship_title']) . '</div>
            <div class="achievement-text">at</div>
            <div class="company-name">' . htmlspecialchars($data['company_name']) . '</div>
            
            <div class="details">
                <div class="detail-item">
                    <div class="detail-label">Duration</div>
                    <div class="detail-value">' . $duration . '</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Performance Rating</div>
                    <div class="detail-value">
                        <div class="rating-stars">' . generateStars($data['rating']) . '</div>
                        ' . $data['rating'] . ' out of 5 stars
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Completion Date</div>
                    <div class="detail-value">' . date('F j, Y', strtotime($data['completion_date'])) . '</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Industry</div>
                    <div class="detail-value">' . htmlspecialchars($data['industry']) . '</div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <div class="signatures">
                <div class="signature">
                    <div class="signature-line"></div>
                    <div class="signature-title">Program Director</div>
                </div>
                <div class="signature">
                    <div class="signature-line"></div>
                    <div class="signature-title">Academic Supervisor</div>
                </div>
            </div>
            
            <div class="verification">
                <div style="font-size: 0.9rem; color: #64748b;">
                    <strong>Issued on:</strong> ' . date('F j, Y', strtotime($data['issue_date'])) . ' | 
                    <strong>Verification Code:</strong>
                </div>
                <div class="verification-code">' . htmlspecialchars($data['verification_code']) . '</div>
                <div style="font-size: 0.8rem; color: #64748b; margin-top: 10px;">
                    Verify this certificate at: intern-connect.strathmore.edu/verify
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
}

function calculateDuration($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    
    $months = $interval->m + ($interval->y * 12);
    $days = $interval->d;
    
    if ($months > 0) {
        return $months . ' month' . ($months > 1 ? 's' : '') . 
               ($days > 0 ? ', ' . $days . ' day' . ($days > 1 ? 's' : '') : '');
    } else {
        return $days . ' day' . ($days > 1 ? 's' : '');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Preview | Intern Connect</title>
    <link rel="icon" href="../static/images/title.png">
    <link rel="stylesheet" href="../static/css/index.css">
    <style>
        .preview-container {
            max-width: 1000px;
            margin: 2rem auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .certificate-frame {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            margin: 2rem 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border: 5px solid #1e40af;
            position: relative;
            text-align: center;
            min-height: 600px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .certificate-frame::before {
            content: "";
            position: absolute;
            top: 15px;
            left: 15px;
            right: 15px;
            bottom: 15px;
            border: 2px solid #3b82f6;
            border-radius: 10px;
        }

        .cert-logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .cert-title {
            font-size: 2.5rem;
            color: #1e40af;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .cert-subtitle {
            font-size: 1rem;
            color: #64748b;
            font-style: italic;
            margin-bottom: 30px;
        }

        .cert-recipient {
            font-size: 2rem;
            color: #1e40af;
            font-weight: bold;
            margin: 20px 0;
            text-decoration: underline;
            text-decoration-color: #3b82f6;
        }

        .cert-text {
            font-size: 1.1rem;
            color: #374151;
            margin: 15px 0;
            line-height: 1.6;
        }

        .cert-program {
            font-size: 1.5rem;
            color: #1e40af;
            font-weight: bold;
            margin: 15px 0;
        }

        .cert-company {
            font-size: 1.2rem;
            color: #059669;
            font-weight: 600;
            margin: 10px 0;
        }

        .cert-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 30px 0;
            text-align: left;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cert-detail-item {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }

        .cert-detail-label {
            font-size: 0.8rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .cert-detail-value {
            font-size: 1rem;
            color: #1e293b;
            font-weight: 500;
        }

        .rating-display {
            color: #fbbf24;
            font-size: 1.2rem;
            margin-top: 5px;
        }

        .cert-verification {
            margin-top: 30px;
            padding: 1rem;
            background: #f1f5f9;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 0.9rem;
            color: #64748b;
        }

        .verification-code-display {
            font-family: monospace;
            font-size: 1rem;
            color: #1e40af;
            font-weight: bold;
            margin-top: 5px;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
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
            font-size: 1rem;
        }

        .download-btn:hover {
            background: linear-gradient(135deg, var(--color-indigo-800), var(--color-purple-900));
            transform: translateY(-1px);
        }

        .back-btn {
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
            gap: 0.5rem;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .print-btn {
            background: linear-gradient(135deg, var(--color-cyan-500), var(--color-cyan-400));
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
        }

        .print-btn:hover {
            background: linear-gradient(135deg, var(--color-cyan-400), var(--color-cyan-300));
        }

        @media (max-width: 768px) {
            .certificate-frame {
                padding: 2rem 1rem;
                margin: 1rem 0;
            }
            
            .cert-title {
                font-size: 2rem;
            }
            
            .cert-recipient {
                font-size: 1.5rem;
            }
            
            .cert-details {
                grid-template-columns: 1fr;
            }
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
    
    <header class="header">CERTIFICATE PREVIEW</header>
    
    <nav class="nav">
        <?php if ($user_type === 'admin'): ?>
            <a href="../Dashboards/admin_dashboard.php">Dashboard</a>
            <a href="../Certificates/certificate_list.php">Certificates</a>
        <?php elseif ($user_type === 'employer'): ?>
            <a href="../Dashboards/employer_dashboard.php">Dashboard</a>
        <?php else: ?>
            <a href="../Dashboards/student_dashboard.php">Dashboard</a>
            <a href="../Forms/my_applications.php">My Applications</a>
        <?php endif; ?>
        <a href="../logout.php" style="margin-left: auto;">Log out</a>
    </nav>

    <div class="content">
        <div class="preview-container">
            <h1 style="color: white; margin-bottom: 2rem; text-align: center;">Certificate Preview</h1>
            
            <!-- Certificate Frame -->
            <div class="certificate-frame">
                <div class="cert-logo">IC</div>
                <div class="cert-title">Certificate</div>
                <div class="cert-subtitle">of Internship Completion</div>
                
                <div class="cert-text">This is to certify that</div>
                <div class="cert-recipient"><?php echo htmlspecialchars($data['student_name']); ?></div>
                <div class="cert-text">has successfully completed the internship program</div>
                <div class="cert-program"><?php echo htmlspecialchars($data['internship_title']); ?></div>
                <div class="cert-text">at</div>
                <div class="cert-company"><?php echo htmlspecialchars($data['company_name']); ?></div>
                
                <div class="cert-details">
                    <div class="cert-detail-item">
                        <div class="cert-detail-label">Duration</div>
                        <div class="cert-detail-value"><?php echo calculateDuration($data['start_date'], $data['end_date']); ?></div>
                    </div>
                    <div class="cert-detail-item">
                        <div class="cert-detail-label">Performance Rating</div>
                        <div class="cert-detail-value">
                            <div class="rating-display"><?php echo generateStars($data['rating']); ?></div>
                            <?php echo $data['rating']; ?> out of 5 stars
                        </div>
                    </div>
                    <div class="cert-detail-item">
                        <div class="cert-detail-label">Completion Date</div>
                        <div class="cert-detail-value"><?php echo date('F j, Y', strtotime($data['completion_date'])); ?></div>
                    </div>
                    <div class="cert-detail-item">
                        <div class="cert-detail-label">Industry</div>
                        <div class="cert-detail-value"><?php echo htmlspecialchars($data['industry']); ?></div>
                    </div>
                </div>
                
                <div class="cert-verification">
                    <div>
                        <strong>Issued on:</strong> <?php echo date('F j, Y', strtotime($data['issue_date'])); ?> | 
                        <strong>Verification Code:</strong>
                    </div>
                    <div class="verification-code-display"><?php echo htmlspecialchars($data['verification_code']); ?></div>
                    <div style="margin-top: 10px; font-size: 0.8rem;">
                        Verify this certificate at: intern-connect.strathmore.edu/verify
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <?php if ($user_type === 'admin'): ?>
                    <a href="../Certificates/certificate_list.php" class="back-btn">← Back to Certificates</a>
                <?php elseif ($user_type === 'employer'): ?>
                    <a href="../Dashboards/employer_dashboard.php" class="back-btn">← Back to Dashboard</a>
                <?php else: ?>
                    <a href="../Results/view_results.php?application_id=<?php echo $data['application_id']; ?>" class="back-btn">← Back to Results</a>
                <?php endif; ?>
                
                <button onclick="window.print()" class="print-btn">🖨️ Print Certificate</button>
                <a href="?certificate_id=<?php echo $certificate_id; ?>&action=download" class="download-btn">📄 Download Certificate</a>
            </div>
        </div>
    </div>

    <footer class="footer">Intern Connect &copy; 2025 | All Rights Reserved</footer>

    <script>
        // Print functionality
        function printCertificate() {
            window.print();
        }
        
        // Add print styles when printing
        window.addEventListener('beforeprint', function() {
            document.body.style.background = 'white';
        });
        
        window.addEventListener('afterprint', function() {
            document.body.style.background = '';
        });
    </script>
</body>
</html>
<?php pg_close($conn); ?>
