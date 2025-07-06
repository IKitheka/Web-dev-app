<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "intern_connect";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}


$employer_id = 1;


$sql = "SELECT * FROM employers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employer_id);
$stmt->execute();
$result = $stmt->get_result();

$employer = [
  'company_name' => '',
  'industry' => '',
  'email' => '',
  'phone' => '',
  'location' => '',
  'website' => '',
  'company_size' => '',
  'about_company' => ''
];

if ($result && $result->num_rows > 0) {
  $employer = $result->fetch_assoc();
}

$stmt->close();
$conn->close();
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
  <header class="header">EMPLOYER PROFILE</header>

  <!-- Navigation -->
  <nav class="nav">
    <a href="#">INTERN CONNECT</a>
    <a href="#">Dashboard</a>
    <a href="#">Post Internships</a>
    <a href="#">Applications</a>
    <a href="#">Profile</a>
    <a href="#" style="margin-left: auto;">Log out</a>
  </nav>

  <!-- Main Container -->
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="profile-section">
        <div class="profile-avatar">MS</div>
        <div class="profile-name"><?php echo htmlspecialchars($employer['company_name']); ?></div>
        <div class="profile-description">
          <?php echo htmlspecialchars($employer['industry']); ?><br>
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

        <form action="update_employer.php" method="POST">
          <div class="form-group">
            <label class="form-label">Company Name</label>
            <input type="text" class="form-input" name="company_name" value="<?php echo htmlspecialchars($employer['company_name']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Industry</label>
            <input type="text" class="form-input" name="industry" value="<?php echo htmlspecialchars($employer['industry']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-input" name="email" value="<?php echo htmlspecialchars($employer['email']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="tel" class="form-input" name="phone" value="<?php echo htmlspecialchars($employer['phone']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Location</label>
            <input type="text" class="form-input" name="location" value="<?php echo htmlspecialchars($employer['location']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Website</label>
            <input type="url" class="form-input" name="website" value="<?php echo htmlspecialchars($employer['website']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Company Size</label>
            <select class="form-input" name="company_size">
              <option value="startup" <?php if ($employer['company_size'] == 'startup') echo 'selected'; ?>>Startup (1-10 employees)</option>
              <option value="small" <?php if ($employer['company_size'] == 'small') echo 'selected'; ?>>Small (11-50 employees)</option>
              <option value="medium" <?php if ($employer['company_size'] == 'medium') echo 'selected'; ?>>Medium (51-200 employees)</option>
              <option value="large" <?php if ($employer['company_size'] == 'large') echo 'selected'; ?>>Large (201-1000 employees)</option>
              <option value="enterprise" <?php if ($employer['company_size'] == 'enterprise') echo 'selected'; ?>>Enterprise (1000+ employees)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">About Company</label>
            <textarea class="form-textarea" name="about_company"><?php echo htmlspecialchars($employer['about_company']); ?></textarea>
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
