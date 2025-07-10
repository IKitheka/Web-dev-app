<?php
session_start();
require_once '../database/connection.php';
if (!isset($_SESSION['student_id'])) {
    header('Location: ../Authentication/login.php');
    exit;
}
$conn = create_connection();
$student_id = $_SESSION['student_id'];
$sql = 'SELECT name, email, phone, department, academic_year FROM "Students" WHERE student_id = $1';
$result = pg_query_params($conn, $sql, [$student_id]);
$student = pg_fetch_assoc($result);
$name = $student['name'] ?? '';
$email = $student['email'] ?? '';
$phone = $student['phone'] ?? '';
$department = $student['department'] ?? '';
$academic_year = $student['academic_year'] ?? '';
$about_options = [
    'Aspiring software developer passionate about building impactful solutions.',
    'Enthusiastic learner with a keen interest in AI and data science.',
    'Team player and problem solver, eager to contribute to real-world projects.',
    'Driven by curiosity and a love for technology and innovation.',
    'Focused on building a strong foundation in software engineering.'
];
$gpa_options = ['3.6', '3.8', '3.5', '3.9', '3.7'];
$credits_options = ['90', '100', '85', '110', '95'];
$graduation_year_options = ['2026', '2027', '2028', '2025', '2029'];
$about = $about_options[array_rand($about_options)];
$gpa = $gpa_options[array_rand($gpa_options)];
$credits = $credits_options[array_rand($credits_options)];
$graduation_year = $graduation_year_options[array_rand($graduation_year_options)];
$success_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = $_POST['department'] ?? '';
    $academic_year = $_POST['academic_year'] ?? '';
    $update_sql = 'UPDATE "Students" SET name = $1, phone = $2, department = $3, academic_year = $4 WHERE student_id = $5';
    $update_result = pg_query_params($conn, $update_sql, [
        $name, $phone, $department, $academic_year, $student_id
    ]);
    if ($update_result) {
        $success_message = 'Profile updated successfully!';
        $result = pg_query_params($conn, $sql, [$student_id]);
        $student = pg_fetch_assoc($result);
        $name = $student['name'] ?? '';
        $phone = $student['phone'] ?? '';
        $department = $student['department'] ?? '';
        $academic_year = $student['academic_year'] ?? '';
    }
}
$user_theme = $_COOKIE['user_theme'] ?? 'default';
$last_login = $_COOKIE['last_login'] ?? 'Never';
$student_initials = $_COOKIE['student_initials'] ?? 'JK';
$show_success = isset($_SESSION['name']) && !empty($_SESSION['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Profile</title>
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
  <header class="header">STUDENT PROFILE</header>
  <?php require_once '../includes/navigation.php'; echo render_navigation('profile'); ?>
  <div class="container">
    <aside class="sidebar">
      <div class="profile-section">
        <div class="profile-avatar"><?php echo htmlspecialchars($student_initials); ?></div>
        <div class="profile-name"><?php echo htmlspecialchars($name); ?></div>
        <div class="profile-description">
          Computer Science Student<br>
          Strathmore University
        </div>
      </div>
      <ul class="sidebar-menu">
        <li class="active">Personal Information</li>
        <li>Academic Information</li>
      </ul>
    </aside>
    <main class="main-content">
      <div class="content-card">
        <h2 class="content-title">Personal Information</h2>
        <form method="POST" action="">
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($name); ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($email); ?>" readonly>
            <small style="color: rgba(255,255,255,0.7); font-size: 0.85rem; margin-top: 0.5rem; display: block;">
              Email cannot be changed. Contact admin if needed.
            </small>
          </div>
          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="tel" name="phone" class="form-input" value="<?php echo htmlspecialchars($phone); ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Department</label>
            <select name="department" class="form-input" required>
              <option value="Computer Science" <?php echo ($department === 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
              <option value="Electrical Engineering" <?php echo ($department === 'Electrical Engineering') ? 'selected' : ''; ?>>Electrical Engineering</option>
              <option value="Mechanical Engineering" <?php echo ($department === 'Mechanical Engineering') ? 'selected' : ''; ?>>Mechanical Engineering</option>
              <option value="Civil Engineering" <?php echo ($department === 'Civil Engineering') ? 'selected' : ''; ?>>Civil Engineering</option>
              <option value="Business Administration" <?php echo ($department === 'Business Administration') ? 'selected' : ''; ?>>Business Administration</option>
              <option value="Finance" <?php echo ($department === 'Finance') ? 'selected' : ''; ?>>Finance</option>
              <option value="Marketing" <?php echo ($department === 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
              <option value="Accounting" <?php echo ($department === 'Accounting') ? 'selected' : ''; ?>>Accounting</option>
              <option value="Information Technology" <?php echo ($department === 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
              <option value="Economics" <?php echo ($department === 'Economics') ? 'selected' : ''; ?>>Economics</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Academic Year</label>
            <select name="academic_year" class="form-input" required>
              <option value="1st Year" <?php echo ($academic_year === '1st Year') ? 'selected' : ''; ?>>1st Year</option>
              <option value="2nd Year" <?php echo ($academic_year === '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
              <option value="3rd Year" <?php echo ($academic_year === '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
              <option value="4th Year" <?php echo ($academic_year === '4th Year') ? 'selected' : ''; ?>>4th Year</option>
              <option value="5th Year" <?php echo ($academic_year === '5th Year') ? 'selected' : ''; ?>>5th Year</option>
              <option value="Graduate" <?php echo ($academic_year === 'Graduate') ? 'selected' : ''; ?>>Graduate</option>
              <option value="Masters" <?php echo ($academic_year === 'Masters') ? 'selected' : ''; ?>>Masters</option>
              <option value="PhD" <?php echo ($academic_year === 'PhD') ? 'selected' : ''; ?>>PhD</option>
            </select>
          </div>
          <button type="submit" class="save-button">Save Changes</button>
        </form>
      </div>
      <div class="content-card">
        <h2 class="content-title">Academic Information</h2>
        <div class="stats-container">
          <div class="stat-card">
            <div class="stat-number"><?php echo htmlspecialchars($gpa); ?></div>
            <div class="stat-label">Current GPA</div>
          </div>
          <div class="stat-card">
            <div class="stat-number"><?php echo htmlspecialchars($credits); ?></div>
            <div class="stat-label">Credits Completed</div>
          </div>
          <div class="stat-card">
            <div class="stat-number"><?php echo htmlspecialchars($graduation_year); ?></div>
            <div class="stat-label">Expected Graduation</div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Key Courses Completed</label>
          <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.5rem;">
            <span class="status-approved">Data Structures</span>
            <span class="status-approved">Web Development</span>
            <span class="status-approved">Database Systems</span>
            <span class="status-pending">Machine Learning</span>
            <span class="status-review">Software Engineering</span>
          </div>
        </div>
      </div>
    </main>
  </div>
  <footer class="footer">
    Intern Connect &copy; 2025 | All Rights Reserved
  </footer>
</body>
</html>
