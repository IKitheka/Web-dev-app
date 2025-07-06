<?php
// Start session at the very beginning
session_start();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store form data in session (secure server-side storage)
    $_SESSION['name'] = $_POST['name'] ?? '';
    $_SESSION['email'] = $_POST['email'] ?? '';
    $_SESSION['phone'] = $_POST['phone'] ?? '';
    $_SESSION['department'] = $_POST['department'] ?? '';
    $_SESSION['academic_year'] = $_POST['academic_year'] ?? '';
    $_SESSION['about'] = $_POST['about'] ?? '';
    $_SESSION['gpa'] = $_POST['gpa'] ?? '';
    $_SESSION['credits'] = $_POST['credits'] ?? '';
    $_SESSION['graduation_year'] = $_POST['graduation_year'] ?? '';
    
    // Set cookies for user preferences (less sensitive data)
    setcookie('user_theme', $_POST['theme'] ?? 'default', time() + (86400 * 30)); // 30 days
    setcookie('last_login', date('Y-m-d H:i:s'), time() + (86400 * 30));
    setcookie('student_initials', substr($_POST['name'] ?? 'UN', 0, 2), time() + (86400 * 30));
    
    // Redirect to prevent form resubmission on refresh
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    // Destroy session
    session_destroy();
    
    // Clear cookies by setting them to expire in the past
    setcookie('user_theme', '', time() - 3600);
    setcookie('last_login', '', time() - 3600);
    setcookie('student_initials', '', time() - 3600);
    
    // Redirect to login page (or current page)
    header('Location: login.php');
    exit;
}

// Get data from session (preferred) or set defaults
$name = $_SESSION['name'] ?? 'John Kamau';
$email = $_SESSION['email'] ?? 'john.kamau@strathmore.edu';
$phone = $_SESSION['phone'] ?? '+254123456789';
$department = $_SESSION['department'] ?? 'Computer Science';
$academic_year = $_SESSION['academic_year'] ?? '2nd Year';
$about = $_SESSION['about'] ?? 'Computer science student at Strathmore University with a passion for AI and Machine Learning. Looking for internship opportunities to apply my technical skills and gain real-world experience in software development.';
$gpa = $_SESSION['gpa'] ?? '3.7';
$credits = $_SESSION['credits'] ?? '65';
$graduation_year = $_SESSION['graduation_year'] ?? '2027';

// Get data from cookies
$user_theme = $_COOKIE['user_theme'] ?? 'default';
$last_login = $_COOKIE['last_login'] ?? 'Never';
$student_initials = $_COOKIE['student_initials'] ?? 'JK';

// Display success message if form was submitted
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
  <!-- Aurora Background Animation -->
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

  <!-- Header -->
  <header class="header">
    <span class="brand">INTERN CONNECT</span>
    <h1>STUDENT PROFILE</h1>
  </header>

  <!-- Navigation -->
  <nav class="nav">
    <a href="student_dashboard.html">Dashboard</a>
    <a href="browse_internships.html">Browse Internships</a>
    <a href="my_applications.html">My Applications</a>
    <a href="student_profile.html" class="active">Profile</a>
    <a href="?logout=1" style="margin-left: auto;">Log out</a>
  </nav>

  <!-- Main Container -->
  <div class="container">
    <!-- Sidebar -->
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
        <li>Academic Details</li>
        <li>Resume & Documents</li>
        <li>Skills & Interests</li>
      </ul>
    </aside>

    <!-- Main Content -->
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
          
          <div class="form-group">
            <label class="form-label">About Me</label>
            <textarea name="about" class="form-textarea" rows="4" placeholder="Tell us about yourself, your interests, and career goals..."><?php echo htmlspecialchars($about); ?></textarea>
          </div>
          
          <button type="submit" class="save-button">Save Changes</button>
        </form>
      </div>

      <!-- Academic Information Card -->
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

  <!-- Footer -->
  <footer class="footer">
    Intern Connect &copy; 2025 | All Rights Reserved
  </footer>


</body>
</html>
