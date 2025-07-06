<?php
// Start session at the very beginning
session_start();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store form data in session (secure server-side storage)
    $_SESSION['company_name'] = $_POST['company_name'] ?? '';
    $_SESSION['industry'] = $_POST['industry'] ?? '';
    $_SESSION['email'] = $_POST['email'] ?? '';
    $_SESSION['phone'] = $_POST['phone'] ?? '';
    $_SESSION['location'] = $_POST['location'] ?? '';
    $_SESSION['website'] = $_POST['website'] ?? '';
    $_SESSION['company_size'] = $_POST['company_size'] ?? '';
    $_SESSION['about'] = $_POST['about'] ?? '';
    
    // Set cookies for user preferences (less sensitive data)
    setcookie('user_theme', $_POST['theme'] ?? 'default', time() + (86400 * 30)); // 30 days
    setcookie('last_login', date('Y-m-d H:i:s'), time() + (86400 * 30));
    setcookie('company_initials', substr($_POST['company_name'] ?? 'UN', 0, 2), time() + (86400 * 30));
    
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
    setcookie('company_initials', '', time() - 3600);
    
    // Redirect to login page (or current page)
    header('Location: login.php');
    exit;
}

// Get data from session (preferred) or set defaults
$company_name = $_SESSION['company_name'] ?? 'Microsoft Kenya';
$industry = $_SESSION['industry'] ?? 'Information Technology';
$email = $_SESSION['email'] ?? 'careers@microsoft.co.ke';
$phone = $_SESSION['phone'] ?? '+254123456789';
$location = $_SESSION['location'] ?? 'Nairobi, Kenya';
$website = $_SESSION['website'] ?? 'https://www.microsoft.com/en-ke';
$company_size = $_SESSION['company_size'] ?? 'large';
$about = $_SESSION['about'] ?? 'Microsoft Kenya is a leading technology company specializing in software development, cloud solutions, and digital transformation. We are committed to empowering students and professionals through innovative internship programs and career development opportunities.';

// Get data from cookies
$user_theme = $_COOKIE['user_theme'] ?? 'default';
$last_login = $_COOKIE['last_login'] ?? 'Never';
$company_initials = $_COOKIE['company_initials'] ?? 'MS';

// Display success message if form was submitted
$show_success = isset($_SESSION['company_name']) && !empty($_SESSION['company_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Intern Connect | Employer Profile</title>
  <link rel="icon" type="image/x-icon" href="/static/images/title.png">
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
    EMPLOYER PROFILE
  </header>

  <!-- Navigation -->
  <nav class="nav">
    <a href="#">INTERN CONNECT</a>
    <a href="#">Dashboard</a>
    <a href="#">Post Internships</a>
    <a href="#">Applications</a>
    <a href="#">Profile</a>
    <a href="?logout=1" style="margin-left: auto;">Log out</a>
  </nav>

  <!-- Main Container -->
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="profile-section">
        <div class="profile-avatar"><?php echo htmlspecialchars($company_initials); ?></div>
        <div class="profile-name"><?php echo htmlspecialchars($company_name); ?></div>
        <div class="profile-description">
          <?php echo htmlspecialchars($industry); ?><br>
          Leading Tech Company
        </div>
      </div>
      
      <ul class="sidebar-menu">
        <li class="active">Company Information</li>
        <li>Job Postings</li>
        <li>Applications</li>
        <li>Settings</li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <div class="content-card">
        <h2 class="content-title">Company Information</h2>
        
        <form method="POST" action="">
          <div class="form-group">
            <label class="form-label">Company Name</label>
            <input type="text" name="company_name" class="form-input" value="<?php echo htmlspecialchars($company_name); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Industry</label>
            <input type="text" name="industry" class="form-input" value="<?php echo htmlspecialchars($industry); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($email); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="tel" name="phone" class="form-input" value="<?php echo htmlspecialchars($phone); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-input" value="<?php echo htmlspecialchars($location); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Website</label>
            <input type="url" name="website" class="form-input" value="<?php echo htmlspecialchars($website); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Company Size</label>
            <select name="company_size" class="form-input">
              <option value="startup" <?php echo ($company_size === 'startup') ? 'selected' : ''; ?>>Startup (1-10 employees)</option>
              <option value="small" <?php echo ($company_size === 'small') ? 'selected' : ''; ?>>Small (11-50 employees)</option>
              <option value="medium" <?php echo ($company_size === 'medium') ? 'selected' : ''; ?>>Medium (51-200 employees)</option>
              <option value="large" <?php echo ($company_size === 'large') ? 'selected' : ''; ?>>Large (201-1000 employees)</option>
              <option value="enterprise" <?php echo ($company_size === 'enterprise') ? 'selected' : ''; ?>>Enterprise (1000+ employees)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">About Company</label>
            <textarea name="about" class="form-textarea"><?php echo htmlspecialchars($about); ?></textarea>
          </div>

          <button type="submit" class="save-button">Save changes</button>
        </form>
      </div>
    </main>
  </div>

  <!-- Footer -->
  <footer class="footer">
    Intern Connect &copy; 2025 | All Rights Reserved
  </footer>
</body>
</html>
