<?php
session_start();
require_once '../database/connection.php';
require_once '../database/auth_helpers.php';
require_once '../includes/navigation.php';

require_auth('student');

$conn = create_connection();
if (!$conn) {
    set_flash_message('Database connection failed. Please try again later.', 'error');
    header('Location: ../Forms/browse_internship.php');
    exit;
}

$student_id = get_current_user_id();
$internship_id = $_GET['internship_id'] ?? '';
$internship = null;
$error = '';
$success = '';

if (!validate_uuid($internship_id)) {
    set_flash_message('Invalid internship selected.', 'error');
    header('Location: ../Forms/browse_internship.php');
    exit;
}

$internship_query = 'SELECT i.title, e.company_name, i.location, i.duration, i.start_date, i.end_date, i.requirements FROM "Internships" i JOIN "Employers" e ON i.employer_id = e.employer_id WHERE i.internship_id = $1 AND i.is_active = TRUE';
$internship_result = safe_query($conn, $internship_query, [$internship_id]);
if ($internship_result && pg_num_rows($internship_result) === 1) {
    $internship = pg_fetch_assoc($internship_result);
} else {
    set_flash_message('Internship not found or inactive.', 'error');
    header('Location: ../Forms/browse_internship.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $cover_letter = trim($_POST['cover_letter'] ?? '');
        $resume_url = trim($_POST['resume_url'] ?? '');
        if (empty($cover_letter) || empty($resume_url)) {
            $error = 'All fields are required.';
        } elseif (!filter_var($resume_url, FILTER_VALIDATE_URL)) {
            $error = 'Resume URL must be a valid link.';
        } else {
            $dup_query = 'SELECT 1 FROM "Applications" WHERE student_id = $1 AND internship_id = $2';
            $dup_result = safe_query($conn, $dup_query, [$student_id, $internship_id]);
            if ($dup_result && pg_num_rows($dup_result) > 0) {
                $error = 'You have already applied for this internship.';
            } else {
                $insert_query = 'INSERT INTO "Applications" (student_id, internship_id, cover_letter, resume_url) VALUES ($1, $2, $3, $4)';
                $insert_result = safe_query($conn, $insert_query, [$student_id, $internship_id, $cover_letter, $resume_url]);
                if ($insert_result) {
                    set_flash_message('Application submitted successfully!', 'success');
                    header('Location: ../Forms/my_applications.php');
                    exit;
                } else {
                    $error = 'Failed to submit application. Please try again.';
                }
            }
        }
    }
}

$csrf_token = generate_csrf_token();
$flash = get_flash_message();
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Apply for Internship</title>
  <link rel="icon" type="image/x-icon" href="../static/images/title.png">
  <link rel="stylesheet" href="../static/css/index.css">
  <style>
    .apply-details-card {
      max-width: 900px;
      margin: 0 auto 2.5rem auto;
      background: rgba(255,255,255,0.08);
      border-radius: 18px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.10);
      border: 1px solid rgba(255,255,255,0.18);
      overflow: hidden;
    }
    .apply-details-table {
      width: 100%;
      border-collapse: collapse;
    }
    .apply-details-table th, .apply-details-table td {
      padding: 18px 20px;
      font-size: 1rem;
      color: white;
      background: none;
      border-bottom: 1px solid rgba(255,255,255,0.10);
      text-align: left;
    }
    .apply-details-table th {
      font-weight: 600;
      width: 180px;
      background: rgba(255,255,255,0.06);
      text-transform: uppercase;
      letter-spacing: 1px;
      font-size: 0.95rem;
    }
    .apply-details-table tr:last-child td {
      border-bottom: none;
    }
    .apply-form-wide {
      max-width: 700px;
      margin: 0 auto;
    }
    @media (max-width: 900px) {
      .apply-details-card, .apply-form-wide { max-width: 98vw; }
    }
  </style>
  <?php echo get_navigation_styles(); ?>
</head>
<body>
  <header class="header">APPLY FOR INTERNSHIP</header>
  <nav class="nav">
    <a href="#">INTERN CONNECT</a>
    <a href="../Dashboards/student_dashboard.php">Dashboard</a>
    <a href="../Forms/browse_internship.php">Browse Internships</a>
    <a href="../Forms/my_applications.php">My Applications</a>
    <a href="../Profiles/student_profile.php">Profile</a>
    <a href="../logout.php" style="margin-left: auto;">Log out</a>
  </nav>
  <div class="container form-container" style="max-width: 1100px;">
    <div class="apply-details-card">
      <table class="apply-details-table">
        <tr><th>📝 Title</th><td><?php echo safe_output($internship['title']); ?></td></tr>
        <tr><th>🏢 Company</th><td><?php echo safe_output($internship['company_name']); ?></td></tr>
        <tr><th>📍 Location</th><td><?php echo safe_output($internship['location']); ?></td></tr>
        <tr><th>⏱️ Duration</th><td><?php echo safe_output($internship['duration']); ?></td></tr>
        <tr><th>📅 Dates</th><td><?php echo date('d/m/Y', strtotime($internship['start_date'])) . ' - ' . date('d/m/Y', strtotime($internship['end_date'])); ?></td></tr>
        <tr><th>📋 Requirements</th><td><?php echo safe_output($internship['requirements']); ?></td></tr>
      </table>
    </div>
    <form class="register-form apply-form-wide" method="POST" autocomplete="off">
      <div class="form-heading">Submit Your Application</div>
      <div class="form-group">
        <label class="form-label">Cover Letter *</label>
        <textarea class="form-textarea" name="cover_letter" rows="6" maxlength="2000" required><?php echo safe_output($_POST['cover_letter'] ?? ''); ?></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Resume URL *</label>
        <input class="form-input" type="url" name="resume_url" placeholder="https://drive.google.com/your_resume.pdf" maxlength="255" required value="<?php echo safe_output($_POST['resume_url'] ?? ''); ?>">
      </div>
      <input type="hidden" name="csrf_token" value="<?php echo safe_output($csrf_token); ?>">
      <?php if ($error): ?>
        <div class="error-message" style="margin-bottom: 1rem; text-align:center; display:block;">❌ <?php echo safe_output($error); ?></div>
      <?php elseif ($flash && $flash['type'] === 'error'): ?>
        <div class="error-message" style="margin-bottom: 1rem; text-align:center; display:block;">❌ <?php echo safe_output($flash['message']); ?></div>
      <?php endif; ?>
      <div class="button-container" style="margin-top: 1.5rem;">
        <button class="submit-btn" type="submit">Submit Application</button>
        <a href="../Forms/browse_internship.php" class="btn" style="margin-left: 1rem;">Cancel</a>
      </div>
    </form>
  </div>
  <footer class="footer">Intern Connect &copy; 2025 | All Rights Reserved</footer>
</body>
</html> 