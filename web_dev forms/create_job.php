<?php
session_start();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store job data in session
    $_SESSION['job_data'] = [
        'title' => $_POST['title'] ?? '',
        'duration' => $_POST['duration'] ?? '',
        'location' => $_POST['location'] ?? '',
        'start_date' => $_POST['start_date'] ?? '',
        'end_date' => $_POST['end_date'] ?? '',
        'requirements' => $_POST['requirements'] ?? '',
        'description' => $_POST['description'] ?? ''
    ];
    
    // Set cookies for user preferences
    setcookie('last_location', $_POST['location'] ?? '', time() + (30 * 24 * 60 * 60));
    setcookie('preferred_duration', $_POST['duration'] ?? '', time() + (30 * 24 * 60 * 60));
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF'] . '?saved=1');
    exit;
}

// Get data from session and cookies
$jobData = $_SESSION['job_data'] ?? [];
$lastLocation = $_COOKIE['last_location'] ?? '';
$preferredDuration = $_COOKIE['preferred_duration'] ?? '';
$showSavedMessage = isset($_GET['saved']) && $_GET['saved'] === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Intern Connect | Post a Job</title>
  <link rel="stylesheet" href="index.css">
</head>
<body>
  <!-- Aurora Background -->
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
  <header class="header">POST A JOB</header>
  <nav class="nav">
    <a href="#">INTERN CONNECT</a>
    <a href="#">Dashboard</a>
    <a class="active" href="#">Post a Job</a>
    <a href="#">Manage Listings</a>
    <a href="#">Applicants</a>
    <a href="#">Profile</a>
    <a href="#" style="margin-left: auto;">Log out</a>
  </nav>
  <div class="content">
    <h1 class="welcome">Create New Internship</h1>

    <?php if ($showSavedMessage): ?>
    <div class="message success">
      <strong>Success!</strong> Job saved successfully! Data stored in session.
    </div>
    <?php endif; ?>

    <?php if (!empty($lastLocation) || !empty($preferredDuration)): ?>
    <div class="message info">
      <strong>Welcome back!</strong> Pre-filled from cookies:
      <?php if (!empty($lastLocation)): ?>
        Location: <?php echo htmlspecialchars($lastLocation, ENT_QUOTES); ?>
      <?php endif; ?>
      <?php if (!empty($preferredDuration)): ?>
        Duration: <?php echo htmlspecialchars($preferredDuration, ENT_QUOTES); ?>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="tabs">
      <button class="tab-button active" onclick="showTab('basic')">Basic Info</button>
      <button class="tab-button" onclick="showTab('details')">Details</button>
      <button class="tab-button" onclick="showTab('session')">Session Data</button>
    </div>

    <form class="form-section" method="POST" id="jobForm">
      <div id="basic" class="tab-content active">
        <div class="form-group">
          <label class="form-label">Internship Title</label>
          <input type="text" name="title" class="form-input" 
                 placeholder="e.g. Data Science Intern" 
                 value="<?php echo htmlspecialchars($jobData['title'] ?? '', ENT_QUOTES); ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Duration</label>
          <input type="text" name="duration" class="form-input" 
                 placeholder="e.g. 3 months" 
                 value="<?php echo htmlspecialchars($jobData['duration'] ?? $preferredDuration, ENT_QUOTES); ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Location</label>
          <input type="text" name="location" class="form-input" 
                 placeholder="e.g. Remote / Nairobi" 
                 value="<?php echo htmlspecialchars($jobData['location'] ?? $lastLocation, ENT_QUOTES); ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Start Date</label>
          <input type="date" name="start_date" class="form-input" 
                 value="<?php echo htmlspecialchars($jobData['start_date'] ?? '', ENT_QUOTES); ?>">
        </div>
        <div class="form-group">
          <label class="form-label">End Date</label>
          <input type="date" name="end_date" class="form-input" 
                 value="<?php echo htmlspecialchars($jobData['end_date'] ?? '', ENT_QUOTES); ?>">
        </div>
      </div>

      <div id="details" class="tab-content">
        <div class="form-group">
          <label class="form-label">Requirements</label>
          <textarea name="requirements" class="form-textarea" 
                    placeholder="Required skills, education level..."><?php echo htmlspecialchars($jobData['requirements'] ?? '', ENT_QUOTES); ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-textarea" 
                    placeholder="Job roles, company mission..."><?php echo htmlspecialchars($jobData['description'] ?? '', ENT_QUOTES); ?></textarea>
        </div>
      </div>

      <div class="btn-group">
        <button type="submit" class="btn">Save Job</button>
      </div>
    </form>

    <div id="session" class="tab-content">
      <div class="session-data">
        <h3>Session Information</h3>
        <p><strong>Session ID:</strong> <?php echo htmlspecialchars(session_id(), ENT_QUOTES); ?></p>
        
        <?php if (!empty($jobData)): ?>
          <h4>Current Job Data:</h4>
          <p><strong>Title:</strong> <?php echo htmlspecialchars($jobData['title'] ?? 'Not set', ENT_QUOTES); ?></p>
          <p><strong>Duration:</strong> <?php echo htmlspecialchars($jobData['duration'] ?? 'Not set', ENT_QUOTES); ?></p>
          <p><strong>Location:</strong> <?php echo htmlspecialchars($jobData['location'] ?? 'Not set', ENT_QUOTES); ?></p>
          <p><strong>Start Date:</strong> <?php echo htmlspecialchars($jobData['start_date'] ?? 'Not set', ENT_QUOTES); ?></p>
          <p><strong>End Date:</strong> <?php echo htmlspecialchars($jobData['end_date'] ?? 'Not set', ENT_QUOTES); ?></p>
          <p><strong>Requirements:</strong> <?php echo htmlspecialchars($jobData['requirements'] ?? 'Not set', ENT_QUOTES); ?></p>
          <p><strong>Description:</strong> <?php echo htmlspecialchars($jobData['description'] ?? 'Not set', ENT_QUOTES); ?></p>
        <?php else: ?>
          <p>No session data available. Submit the form to see session data.</p>
        <?php endif; ?>
      </div>

      <div class="session-data">
        <h3>Cookie Information</h3>
        <p><strong>Last Location:</strong> <?php echo htmlspecialchars($lastLocation ?: 'Not set', ENT_QUOTES); ?></p>
        <p><strong>Preferred Duration:</strong> <?php echo htmlspecialchars($preferredDuration ?: 'Not set', ENT_QUOTES); ?></p>
      </div>
    </div>
  </div>
  
  <footer class="footer">Intern Connect &copy; 2025 | All Rights Reserved</footer>
  
  <script>
    function showTab(tabName) {
      // Hide all tab contents
      const tabContents = document.querySelectorAll('.tab-content');
      tabContents.forEach(content => content.classList.remove('active'));
      
      // Remove active class from all buttons
      const tabButtons = document.querySelectorAll('.tab-button');
      tabButtons.forEach(button => button.classList.remove('active'));
      
      // Show selected tab content
      document.getElementById(tabName).classList.add('active');
      
      // Add active class to clicked button
      event.target.classList.add('active');
    }

    document.addEventListener("DOMContentLoaded", function() {
      const form = document.getElementById("jobForm");
      
      form.addEventListener("submit", (e) => {
        // Form will submit normally to PHP
        console.log("Form submitted to PHP backend");
      });
    });
  </script>
</body>
</html>
