<?php
session_start();
require_once '../database/connection.php';
require_once '../database/auth_helpers.php';
require_once '../includes/navigation.php';
require_auth('employer');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('Invalid security token. Please try again.', 'error');
    } else {
        $conn = create_connection();
        $company_name = trim($_POST['company_name'] ?? '');
        $industry = trim($_POST['industry'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $company_size = trim($_POST['company_size'] ?? '');
        $about = trim($_POST['about'] ?? '');
        if (empty($company_name) || empty($industry) || empty($email)) {
            set_flash_message('Company name, industry, and email are required.', 'error');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash_message('Please enter a valid email address.', 'error');
        } elseif (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
            set_flash_message('Please enter a valid website URL.', 'error');
        } else {
            $_SESSION['profile_data'] = [
                'company_name' => $company_name,
                'industry' => $industry,
                'email' => $email,
                'phone' => $phone,
                'location' => $location,
                'website' => $website,
                'company_size' => $company_size,
                'about' => $about
            ];
            setcookie('user_theme', $_POST['theme'] ?? 'default', time() + (86400 * 30));
            setcookie('last_profile_update', date('Y-m-d H:i:s'), time() + (86400 * 30));
            setcookie('company_initials', strtoupper(substr($company_name, 0, 2)), time() + (86400 * 30));
            setcookie('preferred_industry', $industry, time() + (86400 * 30));
            $employer_id = get_current_user_id();
            $sql = 'UPDATE "Employers" SET 
                    company_name = $1, 
                    industry = $2, 
                    email = $3, 
                    phone = $4, 
                    location = $5, 
                    website = $6, 
                    company_size = $7, 
                    about_company = $8, 
                    updated_at = CURRENT_TIMESTAMP 
                    WHERE employer_id = $9';
            $result = safe_query($conn, $sql, [
                $company_name, $industry, $email, $phone, 
                $location, $website, $company_size, $about, $employer_id
            ]);
            if ($result) {
                unset($_SESSION['profile_data']);
                set_flash_message('Profile updated successfully! 🎉', 'success');
            } else {
                set_flash_message('Error updating profile. Please try again.', 'error');
            }
        }
        if ($conn) {
            pg_close($conn);
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?updated=1');
    exit;
}
$conn = create_connection();
$employer_id = get_current_user_id();
$profile_data = $_SESSION['profile_data'] ?? [];
$sql = 'SELECT *, 
        (SELECT COUNT(*) FROM "Internships" WHERE employer_id = $1) as total_internships,
        (SELECT COUNT(*) FROM "Applications" a JOIN "Internships" i ON a.internship_id = i.internship_id WHERE i.employer_id = $1) as total_applications
        FROM "Employers" WHERE employer_id = $1';
$result = safe_query($conn, $sql, [$employer_id]);
if ($result && pg_num_rows($result) > 0) {
    $db_data = pg_fetch_assoc($result);
    $company_name = $profile_data['company_name'] ?? $db_data['company_name'] ?? 'Your Company';
    $industry = $profile_data['industry'] ?? $db_data['industry'] ?? 'Technology';
    $email = $profile_data['email'] ?? $db_data['email'] ?? '';
    $phone = $profile_data['phone'] ?? $db_data['phone'] ?? '';
    $location = $profile_data['location'] ?? $db_data['location'] ?? '';
    $website = $profile_data['website'] ?? $db_data['website'] ?? '';
    $company_size = $profile_data['company_size'] ?? $db_data['company_size'] ?? 'medium';
    $about = $profile_data['about'] ?? $db_data['about_company'] ?? '';
    $total_internships = $db_data['total_internships'] ?? 0;
    $total_applications = $db_data['total_applications'] ?? 0;
    $created_at = $db_data['created_at'] ?? '';
} else {
    $company_name = $profile_data['company_name'] ?? 'Your Company';
    $industry = $profile_data['industry'] ?? 'Technology';
    $email = $profile_data['email'] ?? '';
    $phone = $profile_data['phone'] ?? '';
    $location = $profile_data['location'] ?? '';
    $website = $profile_data['website'] ?? '';
    $company_size = $profile_data['company_size'] ?? 'medium';
    $about = $profile_data['about'] ?? '';
    $total_internships = 0;
    $total_applications = 0;
    $created_at = '';
}
if ($conn) {
    pg_close($conn);
}
$user_theme = $_COOKIE['user_theme'] ?? 'default';
$last_update = $_COOKIE['last_profile_update'] ?? 'Never';
$company_initials = $_COOKIE['company_initials'] ?? strtoupper(substr($company_name, 0, 2));
$preferred_industry = $_COOKIE['preferred_industry'] ?? '';
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
$show_update_message = isset($_GET['updated']) && $_GET['updated'] === '1' && $success_message;
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Intern Connect | Employer Profile</title>
  <link rel="icon" type="image/x-icon" href="../static/images/title.png">
  <link rel="stylesheet" href="../static/css/index.css">
  <?php echo get_navigation_styles(); ?>
  <style>
    .profile-container {
      max-width: 1200px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: 300px 1fr;
      gap: 30px;
      align-items: start;
    }
    .profile-sidebar {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      padding: 25px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      position: sticky;
      top: 20px;
    }
    .profile-avatar {
      width: 80px;
      height: 80px;
      background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      font-weight: bold;
      color: white;
      margin: 0 auto 15px;
      text-transform: uppercase;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    .profile-info {
      text-align: center;
      margin-bottom: 20px;
    }
    .profile-company {
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 5px;
      color: white;
    }
    .profile-industry {
      opacity: 0.8;
      margin-bottom: 10px;
      color: #4ecdc4;
    }
    .profile-stats {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-top: 20px;
    }
    .stat-item {
      background: rgba(255, 255, 255, 0.05);
      padding: 10px;
      border-radius: 8px;
      text-align: center;
    }
    .stat-number {
      font-size: 1.5rem;
      font-weight: bold;
      color: #00ff88;
      display: block;
    }
    .stat-label {
      font-size: 0.8rem;
      opacity: 0.8;
      margin-top: 2px;
    }
    .profile-main {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      padding: 30px;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .tabs {
      display: flex;
      margin-bottom: 25px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 10px;
      padding: 5px;
    }
    .tab-button {
      flex: 1;
      padding: 12px 20px;
      background: transparent;
      border: none;
      color: rgba(255, 255, 255, 0.7);
      cursor: pointer;
      border-radius: 8px;
      transition: all 0.3s ease;
      font-weight: 500;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    .tab-button.active {
      background: rgba(255, 255, 255, 0.2);
      color: white;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      transform: translateY(-2px);
    }
    .tab-button:hover:not(.active) {
      background: rgba(255, 255, 255, 0.1);
      transform: translateY(-1px);
    }
    .tab-content {
      display: none;
      animation: fadeIn 0.3s ease-in;
    }
    .tab-content.active {
      display: block;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-group.full-width {
      grid-column: span 2;
    }
    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: white;
      opacity: 0.9;
    }
    .form-input, .form-textarea {
      width: 100%;
      padding: 12px 15px;
      background: rgba(255, 255, 255, 0.9);
      border: 2px solid transparent;
      border-radius: 8px;
      color: #333;
      font-size: 0.9rem;
      transition: all 0.3s ease;
      box-sizing: border-box;
    }
    .form-input:focus, .form-textarea:focus {
      outline: none;
      border-color: rgba(255, 99, 132, 0.8);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 99, 132, 0.2);
      background: rgba(255, 255, 255, 1);
    }
    .form-textarea {
      resize: vertical;
      min-height: 100px;
    }
    .save-button {
      background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
      border: none;
      padding: 15px 30px;
      border-radius: 25px;
      color: white;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 1rem;
      min-width: 200px;
    }
    .save-button:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
    }
    .save-button:disabled {
      opacity: 0.7;
      cursor: not-allowed;
      transform: none;
    }
    .session-info {
      background: rgba(255, 255, 255, 0.05);
      padding: 20px;
      border-radius: 10px;
      margin-top: 20px;
    }
    .session-info h4 {
      color: #4ecdc4;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
    }
    .info-item {
      background: rgba(255, 255, 255, 0.05);
      padding: 10px 15px;
      border-radius: 8px;
    }
    .info-label {
      font-size: 0.8rem;
      opacity: 0.7;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .info-value {
      font-weight: 500;
      margin-top: 2px;
      color: white;
    }
    .character-counter {
      font-size: 0.8rem;
      opacity: 0.6;
      text-align: right;
      margin-top: 5px;
    }
    .tips-section {
      background: rgba(255, 255, 255, 0.05);
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    .tips-section h4 {
      color: #4ecdc4;
      margin-bottom: 10px;
    }
    .tips-section ul {
      margin: 0;
      padding-left: 20px;
    }
    .tips-section li {
      margin-bottom: 5px;
      opacity: 0.9;
    }
    @media (max-width: 768px) {
      .profile-container {
        grid-template-columns: 1fr;
        gap: 20px;
      }
      .profile-sidebar {
        position: static;
      }
      .form-row {
        grid-template-columns: 1fr;
        gap: 15px;
      }
      .form-group.full-width {
        grid-column: span 1;
      }
      .tabs {
        flex-direction: column;
        gap: 5px;
      }
      .info-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
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
  <?php echo render_page_header('EMPLOYER PROFILE', 'Manage your company information and settings'); ?>
  <?php echo render_navigation('profile'); ?>
  <div class="content">
    <h1 class="welcome">Company Profile Management</h1>
    <?php if ($show_update_message && $success_message): ?>
      <?php echo render_message($success_message, 'success'); ?>
    <?php endif; ?>
    <?php if ($error_message): ?>
      <?php echo render_message($error_message, 'error'); ?>
    <?php endif; ?>
    <?php if (!empty($preferred_industry) && $preferred_industry !== $industry): ?>
      <?php echo render_message("Industry preference detected: {$preferred_industry}. Consider updating if needed.", 'info'); ?>
    <?php endif; ?>
    <div class="profile-container">
      <div class="profile-sidebar">
        <div class="profile-info">
          <div class="profile-avatar"><?php echo safe_output($company_initials); ?></div>
          <div class="profile-company"><?php echo safe_output($company_name); ?></div>
          <div class="profile-industry">🏭 <?php echo safe_output($industry); ?></div>
          <div style="font-size: 0.8rem; opacity: 0.7; margin-top: 5px;">
            🆔 <?php echo safe_output(substr(get_current_user_id(), 0, 8)) . '...'; ?>
          </div>
          <?php if ($created_at): ?>
          <div style="font-size: 0.8rem; opacity: 0.7; margin-top: 2px;">
            📅 Member since <?php echo date('M Y', strtotime($created_at)); ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="profile-stats">
          <div class="stat-item">
            <span class="stat-number"><?php echo $total_internships; ?></span>
            <div class="stat-label">📝 Job Posts</div>
          </div>
          <div class="stat-item">
            <span class="stat-number"><?php echo $total_applications; ?></span>
            <div class="stat-label">👥 Applications</div>
          </div>
        </div>
        <div style="margin-top: 20px; padding: 15px; background: rgba(255, 255, 255, 0.05); border-radius: 8px;">
          <div style="font-size: 0.8rem; opacity: 0.8;">
            <div>🕒 Last Update: <?php echo $last_update !== 'Never' ? date('M d, Y', strtotime($last_update)) : 'Never'; ?></div>
            <div style="margin-top: 5px;">🎨 Theme: <?php echo ucfirst($user_theme); ?></div>
            <div style="margin-top: 5px;">✅ Status: Active</div>
          </div>
        </div>
      </div>
      <div class="profile-main">
        <div class="tips-section">
          <h4>💡 Profile Tips:</h4>
          <ul>
            <li><strong>Complete Profile:</strong> Fill all sections to attract quality candidates</li>
            <li><strong>Company Description:</strong> Highlight your culture and what makes you unique</li>
            <li><strong>Contact Info:</strong> Keep email and phone updated for candidate communications</li>
            <li><strong>Website:</strong> Add your company website to build credibility</li>
          </ul>
        </div>
        <div class="tabs">
          <button class="tab-button active" onclick="showTab('profile')">
            🏢 Company Info
          </button>
          <button class="tab-button" onclick="showTab('settings')">
            ⚙️ Settings
          </button>
          <button class="tab-button" onclick="showTab('session')">
            🔧 Session Data
          </button>
        </div>
        <form method="POST" id="profileForm">
          <input type="hidden" name="csrf_token" value="<?php echo safe_output($csrf_token); ?>">
          <div id="profile" class="tab-content active">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">🏢 Company Name *</label>
                <input type="text" name="company_name" class="form-input" 
                       value="<?php echo safe_output($company_name); ?>" 
                       required maxlength="100" placeholder="Your Company Name">
              </div>
              <div class="form-group">
                <label class="form-label">🏭 Industry *</label>
                <input type="text" name="industry" class="form-input" 
                       value="<?php echo safe_output($industry); ?>" 
                       required maxlength="50" placeholder="Technology, Finance, etc.">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">📧 Email Address *</label>
                <input type="email" name="email" class="form-input" 
                       value="<?php echo safe_output($email); ?>" 
                       required maxlength="100" placeholder="contact@company.com">
              </div>
              <div class="form-group">
                <label class="form-label">📱 Phone Number</label>
                <input type="tel" name="phone" class="form-input" 
                       value="<?php echo safe_output($phone); ?>" 
                       maxlength="20" placeholder="+254 xxx xxx xxx">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">📍 Location</label>
                <input type="text" name="location" class="form-input" 
                       value="<?php echo safe_output($location); ?>" 
                       maxlength="100" placeholder="Nairobi, Kenya">
              </div>
              <div class="form-group">
                <label class="form-label">🌐 Website</label>
                <input type="url" name="website" class="form-input" 
                       value="<?php echo safe_output($website); ?>" 
                       maxlength="200" placeholder="https://www.company.com">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">👥 Company Size</label>
                <select name="company_size" class="form-input">
                  <option value="startup" <?php echo ($company_size === 'startup') ? 'selected' : ''; ?>>Startup (1-10 employees)</option>
                  <option value="small" <?php echo ($company_size === 'small') ? 'selected' : ''; ?>>Small (11-50 employees)</option>
                  <option value="medium" <?php echo ($company_size === 'medium') ? 'selected' : ''; ?>>Medium (51-200 employees)</option>
                  <option value="large" <?php echo ($company_size === 'large') ? 'selected' : ''; ?>>Large (201-1000 employees)</option>
                  <option value="enterprise" <?php echo ($company_size === 'enterprise') ? 'selected' : ''; ?>>Enterprise (1000+ employees)</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">🎨 Theme Preference</label>
                <select name="theme" class="form-input">
                  <option value="default" <?php echo ($user_theme === 'default') ? 'selected' : ''; ?>>Default Theme</option>
                  <option value="dark" <?php echo ($user_theme === 'dark') ? 'selected' : ''; ?>>Dark Theme</option>
                  <option value="light" <?php echo ($user_theme === 'light') ? 'selected' : ''; ?>>Light Theme</option>
                </select>
              </div>
            </div>
            <div class="form-group full-width">
              <label class="form-label">📄 About Company</label>
              <textarea name="about" class="form-textarea" rows="4" 
                        maxlength="1000" placeholder="Tell potential interns about your company culture, values, and what makes working here special..."><?php echo safe_output($about); ?></textarea>
              <div class="character-counter" id="aboutCounter">1000 characters remaining</div>
            </div>
          </div>
          <div id="settings" class="tab-content">
            <div class="session-info">
              <h4>🔧 Application Settings</h4>
              <div class="info-grid">
                <div class="info-item">
                  <div class="info-label">Auto-save Forms</div>
                  <div class="info-value">✅ Enabled</div>
                </div>
                <div class="info-item">
                  <div class="info-label">Email Notifications</div>
                  <div class="info-value">🔔 Active</div>
                </div>
                <div class="info-item">
                  <div class="info-label">Profile Visibility</div>
                  <div class="info-value">👁️ Public</div>
                </div>
                <div class="info-item">
                  <div class="info-label">Data Protection</div>
                  <div class="info-value">🔒 GDPR Compliant</div>
                </div>
              </div>
            </div>
          </div>
          <div id="session" class="tab-content">
            <div class="session-info">
              <h4>🔐 Authentication Information</h4>
              <div class="info-grid">
                <div class="info-item">
                  <div class="info-label">User ID</div>
                  <div class="info-value"><?php echo safe_output(get_current_user_id()); ?></div>
                </div>
                <div class="info-item">
                  <div class="info-label">User Type</div>
                  <div class="info-value">🏢 <?php echo safe_output(ucfirst(get_current_user_type())); ?></div>
                </div>
                <div class="info-item">
                  <div class="info-label">Session ID</div>
                  <div class="info-value"><?php echo safe_output(substr(session_id(), 0, 12)) . '...'; ?></div>
                </div>
                <div class="info-item">
                  <div class="info-label">Authentication</div>
                  <div class="info-value">✅ Verified</div>
                </div>
              </div>
            </div>
            <?php if (!empty($profile_data)): ?>
            <div class="session-info">
              <h4>📝 Current Session Data</h4>
              <div class="info-grid">
                <?php foreach ($profile_data as $key => $value): ?>
                <div class="info-item">
                  <div class="info-label"><?php echo ucfirst(str_replace('_', ' ', $key)); ?></div>
                  <div class="info-value"><?php echo safe_output($value ?: 'Not set'); ?></div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
            <div class="session-info">
              <h4>🍪 Cookie Information</h4>
              <div class="info-grid">
                <div class="info-item">
                  <div class="info-label">Theme Preference</div>
                  <div class="info-value"><?php echo safe_output($user_theme); ?></div>
                </div>
                <div class="info-item">
                  <div class="info-label">Last Update</div>
                  <div class="info-value"><?php echo safe_output($last_update); ?></div>
                </div>
                <div class="info-item">
                  <div class="info-label">Company Initials</div>
                  <div class="info-value"><?php echo safe_output($company_initials); ?></div>
                </div>
                <div class="info-item">
                  <div class="info-label">Preferred Industry</div>
                  <div class="info-value"><?php echo safe_output($preferred_industry ?: 'Not set'); ?></div>
                </div>
              </div>
            </div>
          </div>
          <div style="text-align: center; margin-top: 30px;">
            <button type="submit" class="save-button" id="saveBtn">
              💾 Save Profile Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <footer class="footer">
    Intern Connect &copy; 2025 | All Rights Reserved
  </footer>
  <script>
    function showTab(tabName) {
      const tabContents = document.querySelectorAll('.tab-content');
      tabContents.forEach(content => content.classList.remove('active'));
      const tabButtons = document.querySelectorAll('.tab-button');
      tabButtons.forEach(button => button.classList.remove('active'));
      document.getElementById(tabName).classList.add('active');
      event.target.classList.add('active');
    }
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('profileForm');
      const saveBtn = document.getElementById('saveBtn');
      const inputs = form.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="url"], textarea, select');
      inputs.forEach(input => {
        if (input.name === 'csrf_token') return;
        input.addEventListener('input', function() {
          sessionStorage.setItem('profile_' + this.name, this.value);
        });
        const savedValue = sessionStorage.getItem('profile_' + input.name);
        if (!input.value && savedValue) {
          input.value = savedValue;
        }
      });
      const aboutTextarea = document.querySelector('textarea[name="about"]');
      const aboutCounter = document.getElementById('aboutCounter');
      if (aboutTextarea && aboutCounter) {
        function updateAboutCounter() {
          const remaining = 1000 - aboutTextarea.value.length;
          aboutCounter.textContent = remaining + ' characters remaining';
          aboutCounter.style.color = remaining < 100 ? '#ff6b6b' : '';
        }
        aboutTextarea.addEventListener('input', updateAboutCounter);
        updateAboutCounter();
      }
      form.addEventListener('submit', function(e) {
        const companyName = form.company_name.value.trim();
        const industry = form.industry.value.trim();
        const email = form.email.value.trim();
        if (!companyName || !industry || !email) {
          e.preventDefault();
          alert('⚠️ Company name, industry, and email are required fields.');
          return;
        }
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
          e.preventDefault();
          alert('⚠️ Please enter a valid email address.');
          return;
        }
        const website = form.website.value.trim();
        if (website && !website.startsWith('http://') && !website.startsWith('https://')) {
          e.preventDefault();
          alert('⚠️ Website URL must start with http:// or https://');
          return;
        }
        saveBtn.innerHTML = '⏳ Saving...';
        saveBtn.disabled = true;
        inputs.forEach(input => {
          if (input.name !== 'csrf_token') {
            sessionStorage.removeItem('profile_' + input.name);
          }
        });
      });
      const companyNameInput = form.company_name;
      if (companyNameInput) {
        companyNameInput.addEventListener('input', function() {
          const initials = this.value.trim().substring(0, 2).toUpperCase();
          const avatar = document.querySelector('.profile-avatar');
          if (avatar && initials) {
            avatar.textContent = initials;
          }
        });
      }
      const industryInput = form.industry;
      if (industryInput) {
        industryInput.addEventListener('input', function() {
          const industryDisplay = document.querySelector('.profile-industry');
          if (industryDisplay) {
            industryDisplay.innerHTML = '🏭 ' + (this.value || 'Technology');
          }
        });
      }
      const tooltips = {
        'company_name': 'Your official company name as it appears on legal documents',
        'industry': 'The primary industry or sector your company operates in',
        'email': 'Primary contact email for intern communications',
        'phone': 'Contact phone number (optional but recommended)',
        'location': 'Your company\'s primary location or headquarters',
        'website': 'Your company website URL for credibility',
        'company_size': 'Approximate number of employees in your organization',
        'about': 'Brief description of your company culture and values'
      };
      Object.keys(tooltips).forEach(fieldName => {
        const field = form.querySelector(`[name="${fieldName}"]`);
        if (field) {
          field.title = tooltips[fieldName];
        }
      });
    });
  </script>
</body>
</html>