<?php
session_start();
require_once '../database/connection.php';
require_once '../database/auth_helpers.php';
require_auth();

$conn = create_connection();
$application_id = $_GET['application_id'] ?? null;

if (!$application_id) {
    header('Location: complete_internship.php');
    exit();
}

// Get application details
$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

$application_sql = "
    SELECT a.application_id, a.status, a.application_date,
           s.student_id, s.name as student_name, s.email as student_email,
           i.internship_id, i.title as internship_title, i.start_date, i.end_date,
           e.employer_id, e.company_name,
           r.result_id
    FROM \"Applications\" a
    JOIN \"Students\" s ON a.student_id = s.student_id
    JOIN \"Internships\" i ON a.internship_id = i.internship_id
    JOIN \"Employers\" e ON i.employer_id = e.employer_id
    LEFT JOIN \"Results\" r ON a.application_id = r.application_id
    WHERE a.application_id = $1
";

$result = pg_query_params($conn, $application_sql, [$application_id]);

if (!$result || pg_num_rows($result) === 0) {
    header('Location: complete_internship.php');
    exit();
}

$application = pg_fetch_assoc($result);

// Check if user has permission to complete this internship
if ($user_type === 'employer' && $application['employer_id'] !== $user_id) {
    header('Location: complete_internship.php');
    exit();
}

// Check if feedback already exists
if ($application['result_id']) {
    header('Location: view_results.php?application_id=' . $application_id);
    exit();
}

// Check if internship has ended
$end_date = new DateTime($application['end_date']);
$today = new DateTime();
if ($end_date > $today) {
    $_SESSION['error_message'] = 'Cannot complete internship before end date.';
    header('Location: complete_internship.php');
    exit();
}

$success_message = '';
$error_message = '';

if ($_POST) {
    $employer_feedback = trim($_POST['employer_feedback'] ?? '');
    $rating = intval($_POST['rating'] ?? 0);
    $completion_date = $_POST['completion_date'] ?? date('Y-m-d');
    
    // Validation
    if (empty($employer_feedback)) {
        $error_message = 'Employer feedback is required.';
    } elseif ($rating < 1 || $rating > 5) {
        $error_message = 'Please provide a rating between 1 and 5 stars.';
    } else {
        // Insert into Results table
        $insert_sql = "
            INSERT INTO \"Results\" (application_id, employer_feedback, rating, completion_date)
            VALUES ($1, $2, $3, $4)
        ";
        
        $insert_result = pg_query_params($conn, $insert_sql, [
            $application_id,
            $employer_feedback,
            $rating,
            $completion_date
        ]);
        
        if ($insert_result) {
            $_SESSION['success_message'] = 'Internship completed successfully! Feedback has been recorded.';
            header('Location: complete_internship.php');
            exit();
        } else {
            $error_message = 'Failed to save feedback. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Internship - Feedback | Intern Connect</title>
    <link rel="icon" href="../static/images/title.png">
    <link rel="stylesheet" href="../static/css/index.css">
    <style>
        .feedback-container {
            max-width: 800px;
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

        .rating-section {
            margin: 1.5rem 0;
        }

        .star-rating {
            display: flex;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .star {
            font-size: 2rem;
            color: rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
            user-select: none;
        }

        .star:hover,
        .star.active {
            color: #fbbf24;
            transform: scale(1.1);
        }

        .feedback-textarea {
            width: 100%;
            min-height: 150px;
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

        .rating-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .selected-rating {
            color: var(--color-cyan-300);
            font-weight: 600;
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
    
    <header class="header">COMPLETE INTERNSHIP - FEEDBACK</header>
    
    <nav class="nav">
        <?php if ($user_type === 'admin'): ?>
            <a href="../Dashboards/admin_dashboard.php">Dashboard</a>
            <a href="../Results/complete_internship.php">Complete Internships</a>
        <?php else: ?>
            <a href="../Dashboards/employer_dashboard.php">Dashboard</a>
            <a href="../Results/complete_internship.php">Complete Internships</a>
        <?php endif; ?>
        <a href="../logout.php" style="margin-left: auto;">Log out</a>
    </nav>

    <div class="content">
        <div class="feedback-container">
            <h1 style="color: white; margin-bottom: 2rem; text-align: center;">Complete Internship</h1>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <!-- Internship Summary -->
            <div class="internship-summary">
                <h2 style="color: white; margin: 0 0 1rem 0; font-size: 1.5rem;">Internship Summary</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 0.25rem;">Position</div>
                        <div style="color: white; font-weight: 500; margin-bottom: 1rem;"><?php echo htmlspecialchars($application['internship_title']); ?></div>
                        
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 0.25rem;">Student</div>
                        <div style="color: white; font-weight: 500;"><?php echo htmlspecialchars($application['student_name']); ?></div>
                    </div>
                    <div>
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 0.25rem;">Duration</div>
                        <div style="color: white; font-weight: 500; margin-bottom: 1rem;">
                            <?php echo date('M j, Y', strtotime($application['start_date'])); ?> - 
                            <?php echo date('M j, Y', strtotime($application['end_date'])); ?>
                        </div>
                        
                        <div style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 0.25rem;">Company</div>
                        <div style="color: white; font-weight: 500;"><?php echo htmlspecialchars($application['company_name']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Feedback Form -->
            <form method="POST" action="">
                <div class="rating-section">
                    <label style="color: white; font-weight: 500; display: block; margin-bottom: 1rem;">
                        Overall Rating for Student Performance <span style="color: #ef4444;">*</span>
                    </label>
                    <div class="star-rating" id="starRating">
                        <span class="star" data-rating="1">★</span>
                        <span class="star" data-rating="2">★</span>
                        <span class="star" data-rating="3">★</span>
                        <span class="star" data-rating="4">★</span>
                        <span class="star" data-rating="5">★</span>
                    </div>
                    <div class="rating-display">
                        <span style="color: rgba(255,255,255,0.7);">Selected rating:</span>
                        <span class="selected-rating" id="ratingText">No rating selected</span>
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" value="0">
                </div>

                <div style="margin: 2rem 0;">
                    <label for="employer_feedback" style="color: white; font-weight: 500; display: block; margin-bottom: 1rem;">
                        Detailed Feedback for Student <span style="color: #ef4444;">*</span>
                    </label>
                    <textarea 
                        name="employer_feedback" 
                        id="employer_feedback" 
                        class="feedback-textarea"
                        placeholder="Please provide detailed feedback about the student's performance, contributions, skills demonstrated, areas of strength, and any recommendations for improvement..."
                        required
                    ><?php echo htmlspecialchars($_POST['employer_feedback'] ?? ''); ?></textarea>
                </div>

                <div style="margin: 2rem 0;">
                    <label for="completion_date" style="color: white; font-weight: 500; display: block; margin-bottom: 1rem;">
                        Completion Date
                    </label>
                    <input 
                        type="date" 
                        name="completion_date" 
                        id="completion_date"
                        class="form-input"
                        style="width: auto; padding: 0.75rem;"
                        value="<?php echo $_POST['completion_date'] ?? date('Y-m-d'); ?>"
                        max="<?php echo date('Y-m-d'); ?>"
                        required
                    >
                </div>

                <div class="form-actions">
                    <a href="complete_internship.php" class="cancel-btn">Cancel</a>
                    <button type="submit" class="submit-btn">✅ Complete Internship</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer">Intern Connect &copy; 2025 | All Rights Reserved</footer>

    <script>
        // Star rating functionality
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('ratingInput');
        const ratingText = document.getElementById('ratingText');
        
        let selectedRating = 0;
        
        stars.forEach((star, index) => {
            star.addEventListener('click', () => {
                selectedRating = index + 1;
                updateStars();
                updateRatingDisplay();
            });
            
            star.addEventListener('mouseover', () => {
                highlightStars(index + 1);
            });
        });
        
        document.getElementById('starRating').addEventListener('mouseleave', () => {
            updateStars();
        });
        
        function highlightStars(rating) {
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }
        
        function updateStars() {
            highlightStars(selectedRating);
        }
        
        function updateRatingDisplay() {
            ratingInput.value = selectedRating;
            if (selectedRating === 0) {
                ratingText.textContent = 'No rating selected';
                ratingText.style.color = 'rgba(255,255,255,0.7)';
            } else {
                const ratingLabels = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
                ratingText.textContent = `${selectedRating}/5 - ${ratingLabels[selectedRating]}`;
                ratingText.style.color = 'var(--color-cyan-300)';
            }
        }
        
        // Form validation
        document.querySelector('form').addEventListener('submit', (e) => {
            if (selectedRating === 0) {
                e.preventDefault();
                alert('Please select a rating before submitting.');
                return false;
            }
            
            const feedback = document.getElementById('employer_feedback').value.trim();
            if (feedback.length < 10) {
                e.preventDefault();
                alert('Please provide more detailed feedback (at least 10 characters).');
                return false;
            }
        });
    </script>
</body>
</html>
