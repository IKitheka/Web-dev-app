<?php
session_start();
require_once '../database/connection.php';
require_once '../database/auth_helpers.php';
require_auth();

$conn = create_connection();
$application_id = $_GET['application_id'] ?? null;
$student_id = $_SESSION['user_id'];

if (!$application_id) {
    header('Location: ../Forms/my_applications.php');
    exit();
}

$result_sql = "
    SELECT r.result_id, r.employer_feedback, r.student_feedback, r.rating, r.completion_date,
           a.application_id, a.status,
           s.student_id, s.name as student_name,
           i.title as internship_title, i.start_date, i.end_date,
           e.company_name
    FROM \"Results\" r
    JOIN \"Applications\" a ON r.application_id = a.application_id
    JOIN \"Students\" s ON a.student_id = s.student_id
    JOIN \"Internships\" i ON a.internship_id = i.internship_id
    JOIN \"Employers\" e ON i.employer_id = e.employer_id
    WHERE a.application_id = $1 AND s.student_id = $2
";

$result = pg_query_params($conn, $result_sql, [$application_id, $student_id]);

if (!$result || pg_num_rows($result) === 0) {
    $_SESSION['error_message'] = 'Result not found or access denied.';
    header('Location: ../Forms/my_applications.php');
    exit();
}

$data = pg_fetch_assoc($result);

if (!empty($data['student_feedback'])) {
    $_SESSION['info_message'] = 'You have already submitted feedback for this internship.';
    header('Location: view_results.php?application_id=' . $application_id);
    exit();
}

$error_message = '';

if ($_POST) {
    $student_feedback = trim($_POST['student_feedback'] ?? '');
    
    if (empty($student_feedback)) {
        $error_message = 'Please provide your feedback about the internship experience.';
    } elseif (strlen($student_feedback) < 20) {
        $error_message = 'Please provide more detailed feedback (at least 20 characters).';
    } else {
        $update_sql = "
            UPDATE \"Results\" 
            SET student_feedback = $1
            WHERE result_id = $2
        ";
        
        $update_result = pg_query_params($conn, $update_sql, [$student_feedback, $data['result_id']]);
        
        if ($update_result) {
            $_SESSION['success_message'] = 'Your feedback has been submitted successfully!';
            header('Location: view_results.php?application_id=' . $application_id);
            exit();
        } else {
            $error_message = 'Failed to save your feedback. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Your Feedback | Intern Connect</title>
    <link rel="icon" href="../static/images/title.png">
    <link rel="stylesheet" href="../static/css/index.css">
    <style>
        .feedback-container {
            max-width: 700px;
            margin: 2rem auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .internship-summary {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }

        .feedback-textarea {
            width: 100%;
            min-height: 200px;
            padding: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
            line-height: 1.5;
            resize: vertical;
            font-family: inherit;
            backdrop-filter: blur(5px);
        }

        .feedback-textarea:focus {
            outline: none;
            border-color: var(--color-cyan-400);
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
            background: rgba(255, 255, 255, 0.15);
        }

        .feedback-textarea::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .employer-feedback-preview {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }

        .rating-display {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .rating-stars {
            font-size: 1.2rem;
            color: #fbbf24;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--color-cyan-500), var(--color-cyan-400));
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, var(--color-cyan-400), var(--color-cyan-300));
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

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .feedback-tips {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .tips-list {
            color: rgba(255, 255, 255, 0.8);
            margin: 0.5rem 0 0 1rem;
            font-size: 0.9rem;
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
    
    <header class="header">ADD YOUR FEEDBACK</header>
    
    <nav class="nav">
        <a href="../Dashboards/student_dashboard.php">Dashboard</a>
        <a href="../Forms/my_applications.php">My Applications</a>
        <a href="../logout.php" style="margin-left: auto;">Log out</a>
    </nav>

    <div class="content">
        <div class="feedback-container">
            <h1 style="color: white; margin-bottom: 2rem; text-align: center;">Share Your Internship Experience</h1>
            
            <?php if ($error_message): ?>
                <div class="alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <!-- Internship Summary -->
            <div class="internship-summary">
                <h2 style="color: white; margin: 0 0 1rem 0; font-size: 1.3rem;"><?php echo htmlspecialchars($data['internship_title']); ?></h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 0.25rem;">Company</div>
                        <div style="color: white; font-weight: 500;"><?php echo htmlspecialchars($data['company_name']); ?></div>
                    </div>
                    <div>
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 0.25rem;">Completed</div>
                        <div style="color: white; font-weight: 500;"><?php echo date('F j, Y', strtotime($data['completion_date'])); ?></div>
                    </div>
                </div>
            </div>

            <!-- Employer's Rating Preview -->
            <div class="employer-feedback-preview">
                <h3 style="color: white; margin: 0 0 1rem 0; font-size: 1.1rem;">Employer's Evaluation</h3>
                <div class="rating-display">
                    <span style="color: rgba(255,255,255,0.7);">Rating:</span>
                    <div class="rating-stars">
                        <?php 
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $data['rating'] ? '★' : '☆';
                        }
                        ?>
                    </div>
                    <span style="color: var(--color-cyan-300); font-weight: 600;">
                        <?php echo $data['rating']; ?>/5
                    </span>
                </div>
                <div style="color: rgba(255,255,255,0.8); font-size: 0.9rem; line-height: 1.5;">
                    <?php echo substr(htmlspecialchars($data['employer_feedback']), 0, 150) . '...'; ?>
                </div>
            </div>

            <!-- Feedback Tips -->
            <div class="feedback-tips">
                <h3 style="color: #3b82f6; margin: 0 0 0.5rem 0; font-size: 1rem;">💡 What to include in your feedback:</h3>
                <ul class="tips-list">
                    <li>What skills did you learn or improve?</li>
                    <li>How was the work environment and company culture?</li>
                    <li>What were the highlights of your experience?</li>
                    <li>Any challenges you faced and how you overcame them?</li>
                    <li>Would you recommend this internship to other students?</li>
                </ul>
            </div>

            <!-- Feedback Form -->
            <form method="POST" action="">
                <div style="margin: 2rem 0;">
                    <label for="student_feedback" style="color: white; font-weight: 500; display: block; margin-bottom: 1rem;">
                        Your Experience & Recommendations <span style="color: #ef4444;">*</span>
                    </label>
                    <textarea 
                        name="student_feedback" 
                        id="student_feedback" 
                        class="feedback-textarea"
                        placeholder="Share your internship experience... What did you learn? How was the work environment? What advice would you give to future interns at this company?"
                        required
                    ><?php echo htmlspecialchars($_POST['student_feedback'] ?? ''); ?></textarea>
                    <div style="color: rgba(255,255,255,0.6); font-size: 0.8rem; margin-top: 0.5rem;">
                        <span id="charCount">0</span> characters (minimum 20 required)
                    </div>
                </div>

                <div class="form-actions">
                    <a href="view_results.php?application_id=<?php echo $application_id; ?>" class="cancel-btn">Cancel</a>
                    <button type="submit" class="submit-btn">📝 Submit Feedback</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer">Intern Connect &copy; 2025 | All Rights Reserved</footer>

    <script>
        // Character counter
        const textarea = document.getElementById('student_feedback');
        const charCount = document.getElementById('charCount');
        
        function updateCharCount() {
            const count = textarea.value.length;
            charCount.textContent = count;
            
            if (count < 20) {
                charCount.style.color = '#ef4444';
            } else {
                charCount.style.color = 'var(--color-cyan-300)';
            }
        }
        
        textarea.addEventListener('input', updateCharCount);
        updateCharCount(); // Initial count
        
        // Form validation
        document.querySelector('form').addEventListener('submit', (e) => {
            const feedback = textarea.value.trim();
            if (feedback.length < 20) {
                e.preventDefault();
                alert('Please provide more detailed feedback (at least 20 characters).');
                textarea.focus();
                return false;
            }
        });
    </script>
</body>
</html>
