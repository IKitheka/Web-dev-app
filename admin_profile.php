<?php

$host = "localhost";        
$user = "root";              
$password = "";              
$database = "intern_connect"; 

$conn = new mysqli($host, $user, $password, $database);


if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}


$sql = "SELECT * FROM admins WHERE id = 1"; // Assumes admin ID is 1
$result = $conn->query($sql);

$admin = [
  'full_name' => '',
  'email' => '',
  'phone' => '',
  'location' => '',
  'role_description' => '',
  'admin_level' => ''
];

if ($result && $result->num_rows > 0) {
  $admin = $result->fetch_assoc();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Intern Connect | Admin Profile</title>
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
    ADMIN PROFILE
  </header>

  <!-- Navigation -->
  <nav class="nav">
    <a href="#">INTERN CONNECT</a>
    <a href="#">Dashboard</a>
    <a href="#">Employers</a>
    <a href="#">Students</a>
    <a href="#">Profile</a>
    <a href="#" style="margin-left: auto;">Log out</a>
  </nav>

  <!-- Main Container -->
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="profile-section">
        <div class="profile-avatar">AD</div>
        <div class="profile-name"><?php echo htmlspecialchars($admin['full_name']); ?></div>
        <div class="profile-description">
          System Administrator<br>
          Intern Connect Platform
        </div>
      </div>

      <ul class="sidebar-menu">
        <li class="active">Personal Information</li>
        <li>System Settings</li>
        <li>Security</li>
        <li>Permissions</li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <div class="content-card">
        <h2 class="content-title">Admin Information</h2>

        <form action="update_admin.php" method="POST">
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-input" name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-input" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="tel" class="form-input" name="phone" value="<?php echo htmlspecialchars($admin['phone']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Location</label>
            <input type="text" class="form-input" name="location" value="<?php echo htmlspecialchars($admin['location']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Role Description</label>
            <textarea class="form-textarea" name="role_description"><?php echo htmlspecialchars($admin['role_description']); ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Admin Level</label>
            <select class="form-input" name="admin_level">
              <option value="super" <?php if ($admin['admin_level'] == 'super') echo 'selected'; ?>>Super Admin</option>
              <option value="admin" <?php if ($admin['admin_level'] == 'admin') echo 'selected'; ?>>Admin</option>
              <option value="moderator" <?php if ($admin['admin_level'] == 'moderator') echo 'selected'; ?>>Moderator</option>
            </select>
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
