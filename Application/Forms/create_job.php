<?php
session_start();
require_once '../database/connection.php';
require_once '../database/auth_helpers.php';
require_once '../includes/navigation.php';
require_auth('employer');
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $duration = trim($_POST['duration'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        $requirements = trim($_POST['requirements'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if (empty($end_date) && !empty($start_date) && !empty($duration)) {
            $matches = [];
            if (preg_match('/(\d+)\s*(month|week|day)/i', $duration, $matches)) {
                $num = (int)$matches[1];
                $unit = strtolower($matches[2]);
                $start = new DateTime($start_date);
                if ($unit === 'month') {
                    $start->modify("+{$num} months");
                } elseif ($unit === 'week') {
                    $start->modify("+{$num} weeks");
                } elseif ($unit === 'day') {
                    $start->modify("+{$num} days");
                }
                $end_date = $start->format('Y-m-d');
            }
        }
        $_SESSION['job_data'] = [
            'title' => $title,
            'duration' => $duration,
            'location' => $location,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'requirements' => $requirements,
            'description' => $description
        ];
        setcookie('last_location', $location, time() + (30 * 24 * 60 * 60));
        setcookie('preferred_duration', $duration, time() + (30 * 24 * 60 * 60));
        setcookie('user_theme', $_POST['theme'] ?? 'default', time() + (30 * 24 * 60 * 60));
        if (empty($title) || empty($duration) || empty($location) || empty($start_date) || empty($end_date) || empty($requirements) || empty($description)) {
            $error = 'All fields are required.';
        } elseif (strtotime($start_date) >= strtotime($end_date)) {
            $error = 'End date must be after start date.';
        } elseif (strtotime($start_date) < strtotime(date('Y-m-d'))) {
            $error = 'Start date cannot be in the past.';
        } else {
            try {
                $conn = create_connection();
                if (!$conn) {
                    throw new Exception("Failed to create database connection");
                }
                $employer_id = get_current_user_id();
                if (!$employer_id) {
                    throw new Exception("No valid employer ID found in session");
                }
                $sql = 'INSERT INTO "Internships" (employer_id, title, duration, location, start_date, end_date, requirements, description, posted_at, is_active) 
                        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, NOW(), TRUE)';
                $result = safe_query($conn, $sql, [
                    $employer_id, $title, $duration, $location, 
                    $start_date, $end_date, $requirements, $description
                ]);
                if ($result) {
                    $message = 'Internship posted successfully! 🎉';
                    unset($_SESSION['job_data']);
                } else {
                    $error = 'Error posting internship. Please try again.';
                }
                pg_close($conn);
            } catch (Exception $e) {
                $error = 'Database error occurred: ' . $e->getMessage();
            }
        }
        if ($message) {
            redirect_with_message($_SERVER['PHP_SELF'] . '?saved=1', $message, 'success');
        }
    }
}
$jobData = $_SESSION['job_data'] ?? [];
$lastLocation = $_COOKIE['last_location'] ?? '';
$preferredDuration = $_COOKIE['preferred_duration'] ?? '';
$userTheme = $_COOKIE['user_theme'] ?? 'default';
$showSavedMessage = isset($_GET['saved']) && $_GET['saved'] === '1';
$flash = get_flash_message();
if ($flash) {
    if ($flash['type'] === 'success') {
        $message = $flash['message'];
    } else {
        $error = $flash['message'];
    }
}
$csrf_token = generate_csrf_token();
$conn = create_connection();
$stats = [];
if ($conn) {
    $employer_id = get_current_user_id();
    $stats_query = 'SELECT 
        (SELECT COUNT(*) FROM "Internships" WHERE employer_id = $1) as total_posts,
        (SELECT COUNT(*) FROM "Applications" a JOIN "Internships" i ON a.internship_id = i.internship_id WHERE i.employer_id = $1) as total_applications';
    $stats_result = safe_query($conn, $stats_query, [$employer_id]);
    if ($stats_result) {
        $stats = pg_fetch_assoc($stats_result);
    }
    pg_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Intern Connect | Post a Job</title>
  <link rel="icon" type="image/x-icon" href="../static/images/title.png">
  <link rel="stylesheet" href="../static/css/index.css">
  <?php echo get_navigation_styles(); ?>
  <style>
    .tabs {
      display: flex;
      margin-bottom: 20px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 10px;
      padding: 5px;
      max-width: 1200px;
      margin-left: auto;
      margin-right: auto;
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
    .session-data {
      background: rgba(255, 255, 255, 0.1);
      padding: 20px;
      border-radius: 10px;
      margin: 15px 0;
    }
    .session-data h3, .session-data h4 {
      color: white;
      margin-bottom: 10px;
    }
    .session-data p {
      color: rgba(255, 255, 255, 0.8);
      margin: 5px 0;
    }
    .stats-overview {
      background: rgba(255, 255, 255, 0.1);
      padding: 20px;
      border-radius: 10px;
      margin: 20px auto;
      max-width: 1200px;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
    }
    .stat-item {
      text-align: center;
      padding: 15px;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 8px;
    }
    .stat-number {
      font-size: 2rem;
      font-weight: bold;
      color: #00ff88;
      display: block;
    }
    .stat-label {
      font-size: 0.9rem;
      opacity: 0.8;
      margin-top: 5px;
    }
    .form-section {
      max-width: 1200px;
      margin: 0 auto;
      background: rgba(255, 255, 255, 0.1);
      padding: 30px;
      border-radius: 15px;
      backdrop-filter: blur(10px);
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-input, .form-textarea {
      transition: all 0.3s ease;
      border: 2px solid transparent;
    }
    .form-input:focus, .form-textarea:focus {
      border-color: rgba(255, 99, 132, 0.8);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 99, 132, 0.2);
    }
    .btn {
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
    .btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
    }
    .btn:disabled {
      opacity: 0.7;
      cursor: not-allowed;
      transform: none;
    }
    .tips-section {
      background: rgba(255, 255, 255, 0.05);
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      max-width: 1200px;
      margin-left: auto;
      margin-right: auto;
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
      .tabs {
        flex-direction: column;
        gap: 5px;
      }
      .tab-button {
        padding: 10px 15px;
      }
      .stats-overview {
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        padding: 15px;
      }
      .stat-number {
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <svg class="bg-animation" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"
       style="position: fixed; top: 0; left: 0; width: 100%; height: 100vh; z-index: -1; opacity: 0.3; pointer-events: none;">
    <path fill="url(#aurora-gradient)" d="M0,128L60,138.7C120,149,240,171,360,154.7C480,139,600,85,720,96C840,107,960,181,1080,197.3C1200,213,1320,171,1380,149.3L1440,128L1440,0L1380,0C1320,0,1200,0,1080,0C960,0,840,0,720,0C600,0,480,0,360,0C240,0,120,0,60,0L0,0Z"></path>
    <defs>
      <linearGradient id="aurora-gradient" x1="0" y1="0" x2="1" y2="1">
        <stop offset="0%" stop-color="#4f46e5"/>
        <stop offset="50%" stop-color="#06b6d4"/>
        <stop offset="100%" stop-color="#8b5cf6"/>
      </linearGradient>
    </defs>
  </svg>
  <?php echo render_page_header('POST A JOB', 'Create amazing internship opportunities'); ?>
  <?php echo render_navigation('manage'); ?>
  <div class="content">
    <h1 class="welcome">Create New Internship</h1>
    <?php echo render_user_context(); ?>
    <?php if ($showSavedMessage && $message): ?>
      <?php echo render_message($message, 'success'); ?>
    <?php endif; ?>
    <?php if ($error): ?>
      <?php echo render_message($error, 'error'); ?>
    <?php endif; ?>
    <?php if (!empty($lastLocation) || !empty($preferredDuration)): ?>
      <?php 
      $prefs = [];
      if (!empty($lastLocation)) $prefs[] = "Location: " . $lastLocation;
      if (!empty($preferredDuration)) $prefs[] = "Duration: " . $preferredDuration;
      echo render_message("Welcome back! Pre-filled: " . implode(", ", $prefs), 'info');
      ?>
    <?php endif; ?>
    <?php if (!empty($stats)): ?>
    <div class="stats-overview">
      <div class="stat-item">
        <span class="stat-number"><?php echo $stats['total_posts'] ?? 0; ?></span>
        <div class="stat-label">📝 Total Job Posts</div>
      </div>
      <div class="stat-item">
        <span class="stat-number"><?php echo $stats['total_applications'] ?? 0; ?></span>
        <div class="stat-label">👥 Total Applications</div>
      </div>
      <div class="stat-item">
        <span class="stat-number"><?php echo !empty($stats['total_posts']) ? round($stats['total_applications'] / max($stats['total_posts'], 1), 1) : 0; ?></span>
        <div class="stat-label">📊 Avg Applications/Post</div>
      </div>
    </div>
    <?php endif; ?>
    <div class="tips-section">
      <h4>💡 Tips for a Great Job Posting:</h4>
      <ul>
        <li><strong>Clear Title:</strong> Use specific job titles like "Frontend Developer Intern" instead of just "Intern"</li>
        <li><strong>Detailed Requirements:</strong> List specific skills, tools, and experience level needed</li>
        <li><strong>Company Culture:</strong> Describe your work environment and what makes your company special</li>
        <li><strong>Growth Opportunities:</strong> Mention learning opportunities and potential for full-time conversion</li>
      </ul>
    </div>
    <div class="tabs">
      <button class="tab-button active" onclick="showTab('basic')">📝 Basic Info</button>
      <button class="tab-button" onclick="showTab('details')">📋 Details</button>
      <button class="tab-button" onclick="showTab('session')">🔧 Session Data</button>
    </div>
    <form class="form-section" method="POST" id="jobForm">
      <input type="hidden" name="csrf_token" value="<?php echo safe_output($csrf_token); ?>">
      <div id="basic" class="tab-content active">
        <div class="form-group">
          <label class="form-label">📝 Internship Title *</label>
          <input type="text" name="title" class="form-input" 
                 placeholder="e.g. Frontend Developer Intern" 
                 value="<?php echo safe_output($jobData['title'] ?? ''); ?>" 
                 required maxlength="100">
          <small style="opacity: 0.8;">Be specific and clear about the role</small>
        </div>
        <div class="form-group">
          <label class="form-label">⏱️ Duration *</label>
          <input type="text" name="duration" class="form-input" 
                 placeholder="e.g. 3 months, 6 months, Summer 2025" 
                 value="<?php echo safe_output($jobData['duration'] ?? $preferredDuration); ?>" 
                 required maxlength="50">
        </div>
        <div class="form-group">
          <label class="form-label">📍 Location *</label>
          <input type="text" name="location" class="form-input" 
                 placeholder="e.g. Remote, Nairobi - Kenya, Hybrid" 
                 value="<?php echo safe_output($jobData['location'] ?? $lastLocation); ?>" 
                 required maxlength="100">
        </div>
        <div class="form-group">
          <label class="form-label">📅 Start Date *</label>
          <input type="date" name="start_date" class="form-input" 
                 value="<?php echo safe_output($jobData['start_date'] ?? ''); ?>" 
                 min="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">📅 End Date *</label>
          <input type="date" name="end_date" class="form-input" 
                 value="<?php echo safe_output($jobData['end_date'] ?? ''); ?>" 
                 min="<?php echo date('Y-m-d'); ?>" required>
        </div>
      </div>
      <div id="details" class="tab-content">
        <div class="form-group">
          <label class="form-label">📋 Requirements *</label>
          <textarea name="requirements" class="form-textarea" rows="4"
                    placeholder="• Programming skills in JavaScript, Python, or Java&#10;• Currently pursuing Computer Science degree&#10;• Strong communication skills&#10;• Experience with Git (preferred)" 
                    maxlength="1000"><?php echo safe_output($jobData['requirements'] ?? ''); ?></textarea>
          <small style="opacity: 0.8;">List specific skills, education level, and experience needed</small>
        </div>
        <div class="form-group">
          <label class="form-label">📄 Job Description *</label>
          <textarea name="description" class="form-textarea" rows="6"
                    placeholder="Join our dynamic team as a Frontend Developer Intern! You'll work on exciting projects, learn from experienced developers, and contribute to real products used by thousands of users.&#10;&#10;What you'll do:&#10;• Build responsive web applications&#10;• Collaborate with designers and backend developers&#10;• Learn modern frameworks like React or Vue.js&#10;• Participate in code reviews and team meetings" 
                    maxlength="2000"><?php echo safe_output($jobData['description'] ?? ''); ?></textarea>
          <small style="opacity: 0.8;">Describe the role, responsibilities, and what makes this opportunity special</small>
        </div>
        <div class="form-group">
          <label class="form-label">🎨 Theme Preference</label>
          <select name="theme" class="form-input">
            <option value="default" <?php echo ($userTheme === 'default') ? 'selected' : ''; ?>>Default Theme</option>
            <option value="dark" <?php echo ($userTheme === 'dark') ? 'selected' : ''; ?>>Dark Theme</option>
            <option value="light" <?php echo ($userTheme === 'light') ? 'selected' : ''; ?>>Light Theme</option>
          </select>
        </div>
      </div>
      <div class="btn-group" style="text-align: center; margin-top: 30px;">
        <button type="submit" class="btn" id="submitBtn">
          🚀 Save & Publish Internship
        </button>
      </div>
    </form>
    <div id="session" class="tab-content">
      <div class="session-data">
        <h3>🔐 Authentication Information</h3>
        <p><strong>User ID:</strong> <?php echo safe_output(get_current_user_id()); ?></p>
        <p><strong>User Type:</strong> <?php echo safe_output(get_current_user_type()); ?></p>
        <p><strong>Session ID:</strong> <?php echo safe_output(session_id()); ?></p>
        <p><strong>Authentication:</strong> ✅ Verified</p>
        <?php if (!empty($jobData)): ?>
          <h4>📝 Current Job Data:</h4>
          <p><strong>Title:</strong> <?php echo safe_output($jobData['title'] ?? 'Not set'); ?></p>
          <p><strong>Duration:</strong> <?php echo safe_output($jobData['duration'] ?? 'Not set'); ?></p>
          <p><strong>Location:</strong> <?php echo safe_output($jobData['location'] ?? 'Not set'); ?></p>
          <p><strong>Start Date:</strong> <?php echo safe_output($jobData['start_date'] ?? 'Not set'); ?></p>
          <p><strong>End Date:</strong> <?php echo safe_output($jobData['end_date'] ?? 'Not set'); ?></p>
        <?php else: ?>
          <p>No session data available. Submit the form to see session data.</p>
        <?php endif; ?>
      </div>
      <div class="session-data">
        <h3>🍪 Cookie Information</h3>
        <p><strong>Last Location:</strong> <?php echo safe_output($lastLocation ?: 'Not set'); ?></p>
        <p><strong>Preferred Duration:</strong> <?php echo safe_output($preferredDuration ?: 'Not set'); ?></p>
        <p><strong>Theme:</strong> <?php echo safe_output($userTheme); ?></p>
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
    document.addEventListener("DOMContentLoaded", function() {
      const form = document.getElementById("jobForm");
      const submitBtn = document.getElementById("submitBtn");
      const inputs = form.querySelectorAll('input[type="text"], input[type="date"], textarea, select');
      inputs.forEach(input => {
        if (input.name === 'csrf_token') return;
        input.addEventListener('input', function() {
          sessionStorage.setItem(this.name, this.value);
        });
        const savedValue = sessionStorage.getItem(input.name);
        if (!input.value && savedValue) {
          input.value = savedValue;
        }
      });
      const textareas = form.querySelectorAll('textarea');
      textareas.forEach(textarea => {
        const maxLength = textarea.getAttribute('maxlength');
        if (maxLength) {
          const counter = document.createElement('small');
          counter.style.opacity = '0.6';
          counter.style.float = 'right';
          textarea.parentNode.appendChild(counter);
          function updateCounter() {
            const remaining = maxLength - textarea.value.length;
            counter.textContent = `${remaining} characters remaining`;
            counter.style.color = remaining < 100 ? '#ff6b6b' : '';
          }
          textarea.addEventListener('input', updateCounter);
          updateCounter();
        }
      });
      form.addEventListener("submit", function(e) {
        const requiredFields = [
          { name: "title", label: "Internship Title" },
          { name: "duration", label: "Duration" },
          { name: "location", label: "Location" },
          { name: "start_date", label: "Start Date" },
          { name: "end_date", label: "End Date" },
          { name: "requirements", label: "Requirements" },
          { name: "description", label: "Job Description" }
        ];
        let missing = [];
        requiredFields.forEach(field => {
          const el = form.elements[field.name];
          if (!el || !el.value.trim()) {
            missing.push(field.label);
          }
        });
        if (missing.length > 0) {
          e.preventDefault();
          alert("Please fill in all required fields:\n\n" + missing.join("\n"));
          if (missing.includes("Requirements") || missing.includes("Job Description")) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.getElementById('details').classList.add('active');
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelector('.tab-button[onclick*="details"]').classList.add('active');
          }
          if (missing.some(label => ["Internship Title","Duration","Location","Start Date","End Date"].includes(label))) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.getElementById('basic').classList.add('active');
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelector('.tab-button[onclick*="basic"]').classList.add('active');
          }
          return;
        }
        const startDate = new Date(form.start_date.value);
        const endDate = new Date(form.end_date.value);
        const today = new Date();
        if (startDate < today) {
          e.preventDefault();
          alert('⚠️ Start date cannot be in the past');
          return;
        }
        if (endDate <= startDate) {
          e.preventDefault();
          alert('⚠️ End date must be after start date');
          return;
        }
        submitBtn.innerHTML = '⏳ Publishing...';
        submitBtn.disabled = true;
        inputs.forEach(input => {
          if (input.name !== 'csrf_token') {
            sessionStorage.removeItem(input.name);
          }
        });
      });
      form.start_date.addEventListener('change', function() {
        if (this.value && !form.end_date.value) {
          const startDate = new Date(this.value);
          const suggestedEndDate = new Date(startDate);
          suggestedEndDate.setMonth(suggestedEndDate.getMonth() + 3);
          form.end_date.value = suggestedEndDate.toISOString().split('T')[0];
        }
      });
    });
  </script>
</body>
</html>
