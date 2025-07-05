<?php
// Start session and check authentication
session_start();

// Check if user is logged in, otherwise redirect to login
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    // Clear session data
    session_unset();
    session_destroy();
    
    // Clear cookies
    setcookie('remember_token', '', time() - 3600, '/');
    
    header('Location: login.php');
    exit();
}

// Set a cookie for "remember me" functionality if not set
if (!isset($_COOKIE['user_preferences'])) {
    setcookie('user_preferences', 'theme=light', time() + (86400 * 30), "/");
}

// Initialize employers data
$employers = [];
if (isset($_SESSION['employers'])) {
    $employers = $_SESSION['employers'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['companyName'])) {
        // Add new employer
        $newEmployer = [
            'id' => uniqid(),
            'company' => htmlspecialchars($_POST['companyName']),
            'email' => htmlspecialchars($_POST['email']),
            'industry' => htmlspecialchars($_POST['industry']),
            'phone' => htmlspecialchars($_POST['phone']),
            'status' => htmlspecialchars($_POST['status']),
            'date' => date('Y-m-d'),
            'address' => htmlspecialchars($_POST['address'] ?? ''),
            'website' => htmlspecialchars($_POST['website'] ?? '')
        ];
        
        array_push($employers, $newEmployer);
        $_SESSION['employers'] = $employers;
        
        // Set success message in cookie that will be displayed on next page load
        setcookie('success_message', 'Employer added successfully!', time() + 5, '/');
        
        // Redirect to prevent form resubmission
        header('Location: employers.php');
        exit();
    }
    
    // Handle edit employer
    if (isset($_POST['employerId']) && $_POST['employerId']) {
        // Implementation for editing would go here
    }
}

// Display success message if set in cookie
$successMessage = '';
if (isset($_COOKIE['success_message'])) {
    $successMessage = $_COOKIE['success_message'];
    // Clear the cookie
    setcookie('success_message', '', time() - 3600, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Intern Connect | Employers</title>
  <link rel="stylesheet" href="index.css">
</head>
<body>
  <!-- Background -->
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

  <header class="header">EMPLOYER ACCOUNTS</header>

  <nav class="nav">
    <a href="#">INTERN CONNECT</a>
    <a href="#">Dashboard</a>
    <a class="active" href="#">Employers</a>
    <a href="#">Students</a>
    <a href="#">Profile</a>
    <a href="?logout=1" style="margin-left: auto;">Log out</a>
  </nav>

  <div class="content">
    <h1 class="welcome">Registered Employers</h1>
    <?php if ($successMessage): ?>
      <div class="alert alert-success"><?php echo $successMessage; ?></div>
    <?php endif; ?>
    
    <button class="btn btn-add" onclick="openAddModal()">+ Add New Employer</button>

    <div class="table-section">
      <table class="apps-table" id="employersTable">
        <thead>
          <tr>
            <th>Company</th>
            <th>Email</th>
            <th>Industry</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Date Registered</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="employersTableBody">
          <?php foreach ($employers as $employer): ?>
            <tr data-id="<?php echo $employer['id']; ?>">
              <td><?php echo $employer['company']; ?></td>
              <td><?php echo $employer['email']; ?></td>
              <td><?php echo $employer['industry']; ?></td>
              <td><?php echo $employer['phone']; ?></td>
              <td><span class="status-<?php echo strtolower($employer['status']); ?>"><?php echo $employer['status']; ?></span></td>
              <td><?php echo $employer['date']; ?></td>
              <td>
                <button class="btn" onclick="viewEmployer('<?php echo $employer['id']; ?>')">View</button>
                <button class="btn" onclick="editEmployer('<?php echo $employer['id']; ?>')">Edit</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add/Edit Employer Modal -->
  <div id="employerModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal()">&times;</span>
      <h2 class="form-heading" id="modalTitle">Add New Employer</h2>
      <form id="employerForm" method="POST" action="employers.php">
        <div class="input-group">
          <div class="input-container">
            <input type="text" id="companyName" name="companyName" class="form-input" placeholder="Company Name" required>
          </div>
        </div>
        <div class="input-group">
          <div class="input-container">
            <input type="email" id="email" name="email" class="form-input" placeholder="Email Address" required>
          </div>
        </div>
        <div class="input-group">
          <div class="input-container">
            <select id="industry" name="industry" class="form-input" required>
              <option value="">Select Industry</option>
              <option value="Technology">Technology</option>
              <option value="Healthcare">Healthcare</option>
              <option value="Finance">Finance</option>
              <option value="Agriculture">Agriculture</option>
              <option value="Manufacturing">Manufacturing</option>
              <option value="Education">Education</option>
              <option value="Retail">Retail</option>
              <option value="Construction">Construction</option>
              <option value="Other">Other</option>
            </select>
          </div>
        </div>
        <div class="input-group">
          <div class="input-container">
            <input type="tel" id="phone" name="phone" class="form-input" placeholder="Phone Number" required>
          </div>
        </div>
        <div class="input-group">
          <div class="input-container">
            <select id="status" name="status" class="form-input" required>
              <option value="">Select Status</option>
              <option value="Pending">Pending</option>
              <option value="Approved">Approved</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
        </div>
        <div class="input-group">
          <div class="input-container">
            <input type="text" id="address" name="address" class="form-input" placeholder="Company Address">
          </div>
        </div>
        <div class="input-group">
          <div class="input-container">
            <input type="url" id="website" name="website" class="form-input" placeholder="Company Website (optional)">
          </div>
        </div>
        <input type="hidden" id="employerId" name="employerId">
        <div class="form-actions">
          <button type="submit" class="btn btn-submit" id="submitBtn">Add Employer</button>
          <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <footer class="footer">Intern Connect &copy; <?php echo date('Y'); ?> | All Rights Reserved</footer>

  <script>
    const form = document.getElementById("employerForm");
    const modal = document.getElementById("employerModal");

    function openAddModal() {
      document.getElementById("modalTitle").textContent = "Add New Employer";
      form.reset();
      document.getElementById("employerId").value = "";
      modal.style.display = "block";
    }

    function closeModal() {
      modal.style.display = "none";
    }

    function viewEmployer(id) {
      alert("View functionality would show details for employer ID: " + id);
    }

    function editEmployer(id) {
      // In a real implementation, this would fetch the employer data and populate the form
      document.getElementById("modalTitle").textContent = "Edit Employer";
      document.getElementById("employerId").value = id;
      modal.style.display = "block";
    }

    // Close modal on outside click
    window.onclick = function(event) {
      if (event.target === modal) {
        closeModal();
      }
    };
  </script>
</body>
</html>
