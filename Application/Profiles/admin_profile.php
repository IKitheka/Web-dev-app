<?php
session_start();
require_once '../database/connection.php';
require_once '../database/auth_helpers.php';
require_auth('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('Invalid security token. Please try again.', 'error');
    } else {
        $conn = create_connection();
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $role_description = trim($_POST['role_description'] ?? '');
        $admin_level = trim($_POST['admin_level'] ?? '');
        if (empty($full_name) || empty($email)) {
            set_flash_message('Full name and email are required.', 'error');
        } else {
            $admin_id = get_current_user_id();
            $sql = 'UPDATE "Administrators" SET full_name = $1, email = $2, phone = $3, location = $4, role_description = $5, admin_level = $6, updated_at = CURRENT_TIMESTAMP WHERE admin_id = $7';
            $result = safe_query($conn, $sql, [
                $full_name, $email, $phone, $location, 
                $role_description, $admin_level, $admin_id
            ]);
            if ($result) {
                set_flash_message('Admin profile updated successfully!', 'success');
            } else {
                set_flash_message('Error updating profile. Please try again.', 'error');
            }
        }
        pg_close($conn);
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../Authentication/login.php');
    exit;
}
$conn = create_connection();
$admin_id = get_current_user_id();
$sql = 'SELECT * FROM "Administrators" WHERE admin_id = $1';
$result = safe_query($conn, $sql, [$admin_id]);
$admin = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'location' => '',
    'role_description' => '',
    'admin_level' => ''
];
if ($result && pg_num_rows($result) > 0) {
    $admin = pg_fetch_assoc($result);
}
pg_close($conn);
$flash = get_flash_message();
$success_message = '';
$error_message = '';
if ($flash) {
    if ($flash['type'] === 'success') {
        $success_message = $flash['message'];
    } else {
        $error_message = $flash['message'];
    }
}
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Intern Connect | Admin Profile</title>
  <link rel="icon" type="image/x-icon" href="../static/images/title.png">
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
  <header class="header">ADMIN PROFILE</header>
  <?php require_once '../includes/navigation.php'; echo render_navigation('profile'); ?>
  <div class="container">
    <aside class="sidebar">
      <div class="profile-section">
        <div class="profile-avatar">AD</div>
        <div class="profile-name"><?php echo safe_output($admin['full_name'] ?? 'Admin User'); ?></div>
        <div class="profile-description">
          System Administrator<br>
          Intern Connect Platform
        </div>
        <div style="font-size: 0.8rem; opacity: 0.7; margin-top: 5px;">
          ID: <?php echo safe_output(substr(get_current_user_id(), 0, 8)) . '...'; ?>
        </div>
      </div>
      <ul class="sidebar-menu">
        <li class="active">Personal Information</li>
        <li>System Settings</li>
        <li>Security</li>
        <li>Permissions</li>
      </ul>
    </aside>
    <main class="main-content">
      <div class="content-card">
        <h2 class="content-title">Admin Information</h2>
        <?php if ($success_message): ?>
        <div class="message success" style="background: rgba(0,255,0,0.1); color: #00ff00; padding: 10px; border-radius: 5px; margin: 10px 0;">
          ✅ <?php echo safe_output($success_message); ?>
        </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
        <div class="message error" style="background: rgba(255,0,0,0.1); color: #ff6b6b; padding: 10px; border-radius: 5px; margin: 10px 0;">
          ❌ <?php echo safe_output($error_message); ?>
        </div>
        <?php endif; ?>
        <form action="" method="POST">
          <input type="hidden" name="csrf_token" value="<?php echo safe_output($csrf_token); ?>">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" class="form-input" name="full_name" 
                   value="<?php echo safe_output($admin['full_name'] ?? ''); ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address *</label>
            <input type="email" class="form-input" name="email" 
                   value="<?php echo safe_output($admin['email'] ?? ''); ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="tel" class="form-input" name="phone" 
                   value="<?php echo safe_output($admin['phone'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Location</label>
            <input type="text" class="form-input" name="location" 
                   value="<?php echo safe_output($admin['location'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Role Description</label>
            <textarea class="form-textarea" name="role_description" rows="3"><?php echo safe_output($admin['role_description'] ?? ''); ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Admin Level</label>
            <select class="form-input" name="admin_level">
              <option value="super" <?php if (($admin['admin_level'] ?? '') == 'super') echo 'selected'; ?>>Super Admin</option>
              <option value="admin" <?php if (($admin['admin_level'] ?? '') == 'admin') echo 'selected'; ?>>Admin</option>
              <option value="moderator" <?php if (($admin['admin_level'] ?? '') == 'moderator') echo 'selected'; ?>>Moderator</option>
            </select>
          </div>
          <button type="submit" class="save-button">Save changes</button>
        </form>
        <div style="margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.1); border-radius: 10px;">
          <h4>Session Information:</h4>
          <p><strong>User Type:</strong> <?php echo safe_output(get_current_user_type()); ?></p>
          <p><strong>Admin Level:</strong> <?php echo safe_output($admin['admin_level'] ?? 'admin'); ?></p>
          <p><strong>Session ID:</strong> <?php echo safe_output(session_id()); ?></p>
          <p><strong>Authentication:</strong> ✅ Verified</p>
        </div>
      </div>
    </main>
  </div>
  <footer class="footer">
    Intern Connect &copy; 2025 | All Rights Reserved
  </footer>
</body>
</html>
