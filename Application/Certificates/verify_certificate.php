<?php
require_once '../database/connection.php';

$conn = create_connection();
$verification_code = $_GET['code'] ?? $_POST['verification_code'] ?? '';
$verification_result = null;
$error_message = '';

if (!empty($verification_code)) {
    $verification_code = strtoupper(trim($verification_code));
    
    $verify_sql = "
        SELECT c.certificate_id, c.issue_date, c.verification_code, c.created_at,
               s.name as student_name, s.email as student_email,
               i.title as internship_title, i.start_date, i.end_date, i.description,
               e.company_name, e.industry, e.location,
               r.rating, r.completion_date,
               admin.full_name as issued_by
        FROM \"Certificates\" c
        JOIN \"Applications\" a ON c.application_id = a.application_id
        JOIN \"Students\" s ON a.student_id = s.student_id
        JOIN \"Internships\" i ON a.internship_id = i.internship_id
        JOIN \"Employers\" e ON i.employer_id = e.employer_id
        JOIN \"Results\" r ON a.application_id = r.application_id
        LEFT JOIN \"Administrators\" admin ON c.admin_id = admin.admin_id
        WHERE c.verification_code = $1
    ";
    
    $result = pg_query_params($conn, $verify_sql, [$verification_code]);
    
    if ($result && pg_num_rows($result) > 0) {
        $verification_result = pg_fetch_assoc($result);
    } else {
        $error_message = 'Certificate not found. Please check the verification code and try again.';
    }
}

function generateStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<span style="color: #fbbf24;">★</span>';
        } else {
            $stars .= '<span style="color: rgba(0,0,0,0.3);">☆</span>';
        }
    }
    return $stars;
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
    <title>Certificate Verification | Intern Connect</title>
    <link rel="icon" href="../static/images/title.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            padding: 2rem 0;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 300;
            margin-bottom: 0.5rem;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .container {
            max-width: 900px;
            margin: 3rem auto;
            padding: 0 2rem;
        }

        .verification-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 3rem;
            text-align: center;
        }

        .form-title {
            font-size: 2rem;
            color: #4a5568;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .form-description {
            color: #718096;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        .verification-input-group {
            display: flex;
            gap: 1rem;
            max-width: 500px;
            margin: 0 auto;
            flex-wrap: wrap;
        }

        .verification-input {
            flex: 1;
            padding: 1rem 1.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            text-transform: uppercase;
            font-family: monospace;
            font-weight: 600;
            background: #f7fafc;
            transition: all 0.3s ease;
            min-width: 250px;
        }

        .verification-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .verify-btn {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 150px;
        }

        .verify-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .verification-result {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .success-badge {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .error-badge {
            background: linear-gradient(135deg, #f56565, #e53e3e);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .certificate-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin: 2rem 0;
        }

        .detail-section {
            background: #f7fafc;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }

        .detail-section h3 {
            color: #4a5568;
            margin-bottom: 1rem;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .detail-row {
            margin-bottom: 1rem;
        }

        .detail-label {
            font-size: 0.9rem;
            color: #718096;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-size: 1.1rem;
            color: #2d3748;
            font-weight: 500;
        }

        .student-name {
            font-size: 2rem;
            color: #667eea;
            font-weight: bold;
            text-align: center;
            margin: 1rem 0;
            text-decoration: underline;
            text-decoration-color: #764ba2;
        }

        .program-title {
            font-size: 1.5rem;
            color: #4a5568;
            font-weight: 600;
            text-align: center;
            margin: 1rem 0;
        }

        .company-name {
            font-size: 1.3rem;
            color: #38a169;
            font-weight: 600;
            text-align: center;
            margin: 1rem 0;
        }

        .rating-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .rating-stars {
            font-size: 1.2rem;
        }

        .rating-text {
            color: #4a5568;
            font-weight: 600;
        }

        .verification-footer {
            background: #edf2f7;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            margin-top: 2rem;
        }

        .verification-code-display {
            font-family: monospace;
            font-size: 1.2rem;
            color: #667eea;
            font-weight: bold;
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            margin: 1rem 0;
            display: inline-block;
        }

        .footer {
            text-align: center;
            padding: 2rem 0;
            color: rgba(255, 255, 255, 0.8);
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
        }

        .back-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-top: 2rem;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: #764ba2;
            transform: translateX(-5px);
        }

        .example-codes {
            background: #f7fafc;
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 2rem;
            border: 1px solid #e2e8f0;
        }

        .example-codes h4 {
            color: #4a5568;
            margin-bottom: 1rem;
        }

        .code-example {
            font-family: monospace;
            background: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            margin: 0.5rem 0;
            color: #667eea;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
                margin: 2rem auto;
            }

            .verification-form,
            .verification-result {
                padding: 2rem 1.5rem;
            }

            .verification-input-group {
                flex-direction: column;
                gap: 1rem;
            }

            .verification-input,
            .verify-btn {
                width: 100%;
                min-width: auto;
            }

            .certificate-details {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .header h1 {
                font-size: 2rem;
            }

            .student-name {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏆 Certificate Verification</h1>
        <p>Verify the authenticity of Intern Connect certificates</p>
    </div>

    <div class="container">
        <!-- Verification Form -->
        <div class="verification-form">
            <h2 class="form-title">Enter Verification Code</h2>
            <p class="form-description">
                Please enter the verification code found on the certificate to verify its authenticity.
            </p>
            
            <form method="POST" action="">
                <div class="verification-input-group">
                    <input 
                        type="text" 
                        name="verification_code" 
                        class="verification-input"
                        placeholder="CERT-YYYY-XXX-XX-XX"
                        value="<?php echo htmlspecialchars($verification_code); ?>"
                        maxlength="20"
                        required
                    >
                    <button type="submit" class="verify-btn">🔍 Verify</button>
                </div>
            </form>

            <div class="example-codes">
                <h4>Verification Code Format Examples:</h4>
                <div class="code-example">CERT-2025-001-AJ-TN</div>
                <div class="code-example">CERT-2025-002-BS-GF</div>
                <p style="font-size: 0.9rem; color: #718096; margin-top: 1rem;">
                    The verification code is typically found at the bottom of the certificate.
                </p>
            </div>
        </div>

        <!-- Verification Result -->
        <?php if (!empty($verification_code)): ?>
            <div class="verification-result">
                <?php if ($verification_result): ?>
                    <!-- Success Result -->
                    <div style="text-align: center;">
                        <div class="success-badge">
                            ✅ Certificate Verified - Authentic
                        </div>
                    </div>

                    <div style="text-align: center; margin: 2rem 0;">
                        <h3 style="color: #4a5568; margin-bottom: 1rem;">This certificate was issued to:</h3>
                        <div class="student-name"><?php echo htmlspecialchars($verification_result['student_name']); ?></div>
                        <p style="color: #718096; margin: 1rem 0;">for successful completion of</p>
                        <div class="program-title"><?php echo htmlspecialchars($verification_result['internship_title']); ?></div>
                        <p style="color: #718096; margin: 1rem 0;">at</p>
                        <div class="company-name"><?php echo htmlspecialchars($verification_result['company_name']); ?></div>
                    </div>

                    <div class="certificate-details">
                        <div class="detail-section">
                            <h3>📋 Program Details</h3>
                            <div class="detail-row">
                                <div class="detail-label">Program Duration</div>
                                <div class="detail-value"><?php echo calculateDuration($verification_result['start_date'], $verification_result['end_date']); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Start Date</div>
                                <div class="detail-value"><?php echo date('F j, Y', strtotime($verification_result['start_date'])); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Completion Date</div>
                                <div class="detail-value"><?php echo date('F j, Y', strtotime($verification_result['completion_date'])); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Industry</div>
                                <div class="detail-value"><?php echo htmlspecialchars($verification_result['industry']); ?></div>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h3>🏆 Performance & Certification</h3>
                            <div class="detail-row">
                                <div class="detail-label">Performance Rating</div>
                                <div class="detail-value">
                                    <div class="rating-display">
                                        <div class="rating-stars"><?php echo generateStars($verification_result['rating']); ?></div>
                                        <div class="rating-text"><?php echo $verification_result['rating']; ?>/5</div>
                                    </div>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Certificate Issued</div>
                                <div class="detail-value"><?php echo date('F j, Y', strtotime($verification_result['issue_date'])); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Issued By</div>
                                <div class="detail-value"><?php echo htmlspecialchars($verification_result['issued_by'] ?: 'Intern Connect Administration'); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Company Location</div>
                                <div class="detail-value"><?php echo htmlspecialchars($verification_result['location'] ?: 'Not specified'); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="verification-footer">
                        <h4 style="color: #4a5568; margin-bottom: 1rem;">Verification Details</h4>
                        <p style="color: #718096; margin-bottom: 1rem;">
                            This certificate has been verified as authentic and was issued by Intern Connect.
                        </p>
                        <div>
                            <strong>Verification Code:</strong>
                            <div class="verification-code-display"><?php echo htmlspecialchars($verification_result['verification_code']); ?></div>
                        </div>
                        <p style="color: #718096; font-size: 0.9rem; margin-top: 1rem;">
                            Verified on <?php echo date('F j, Y \a\t g:i A'); ?>
                        </p>
                    </div>

                <?php else: ?>
                    <!-- Error Result -->
                    <div style="text-align: center;">
                        <div class="error-badge">
                            ❌ Certificate Not Found
                        </div>
                        
                        <div style="margin: 2rem 0;">
                            <h3 style="color: #e53e3e; margin-bottom: 1rem;">Verification Failed</h3>
                            <p style="color: #718096; font-size: 1.1rem; line-height: 1.6;">
                                The verification code "<strong><?php echo htmlspecialchars($verification_code); ?></strong>" 
                                was not found in our database.
                            </p>
                            
                            <div style="background: #fed7d7; border: 1px solid #feb2b2; border-radius: 12px; padding: 1.5rem; margin: 2rem 0;">
                                <h4 style="color: #c53030; margin-bottom: 1rem;">Possible reasons:</h4>
                                <ul style="color: #742a2a; text-align: left; max-width: 400px; margin: 0 auto;">
                                    <li>The verification code was entered incorrectly</li>
                                    <li>The certificate may be fraudulent</li>
                                    <li>The code may have been mistyped on the certificate</li>
                                    <li>The certificate may be from a different institution</li>
                                </ul>
                            </div>
                            
                            <p style="color: #718096; margin-top: 2rem;">
                                Please double-check the verification code and try again. If you continue to have issues, 
                                please contact Intern Connect support.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 3rem;">
            <a href="../index.php" class="back-link">← Back to Intern Connect</a>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2025 Intern Connect | Strathmore University | All Rights Reserved</p>
        <p style="font-size: 0.9rem; margin-top: 0.5rem;">
            For support or inquiries, contact: support@intern-connect.strathmore.edu
        </p>
    </div>

    <script>
        document.querySelector('.verification-input').addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^A-Z0-9]/g, '').toUpperCase();
            
            if (value.length > 4) {
                value = value.substring(0, 4) + '-' + value.substring(4);
            }
            if (value.length > 9) {
                value = value.substring(0, 9) + '-' + value.substring(9);
            }
            if (value.length > 13) {
                value = value.substring(0, 13) + '-' + value.substring(13);
            }
            if (value.length > 16) {
                value = value.substring(0, 16) + '-' + value.substring(16);
            }
            
            if (value.length > 20) {
                value = value.substring(0, 20);
            }
            
            e.target.value = value;
        });

        document.querySelector('form').addEventListener('submit', function(e) {
            const input = document.querySelector('.verification-input');
            const code = input.value.trim();
            
            if (code.length < 10) {
                e.preventDefault();
                alert('Please enter a complete verification code (e.g., CERT-2025-001-AJ-TN)');
                input.focus();
                return false;
            }
        });
    </script>
</body>
</html>
<?php pg_close($conn); ?>
