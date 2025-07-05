<?php
// Handle form submissions and cookie operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['admin_name'])) {
        // Set admin name cookie
        setcookie('admin_name', $_POST['admin_name'], time() + (30 * 24 * 60 * 60)); // 30 days
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_POST['update_status'])) {
        // Set success message cookie
        setcookie('success_message', 'Employer status updated successfully!', time() + 300); // 5 minutes
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_POST['view_preference'])) {
        // Set view preference cookie
        setcookie('view_preference', $_POST['view_preference'], time() + (30 * 24 * 60 * 60)); // 30 days
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get values from cookies
$admin_name = $_COOKIE['admin_name'] ?? 'Admin';
$success_message = $_COOKIE['success_message'] ?? '';
$view_preference = $_COOKIE['view_preference'] ?? 'table';

// Clear success message cookie after displaying
if ($success_message) {
    setcookie('success_message', '', time() - 3600);
}

// Sample employer data
$employers = [
    [
        'company' => 'TechNova Inc.',
        'email' => 'contact@technova.com',
        'industry' => 'Technology',
        'phone' => '555-123-4567',
        'status' => 'Approved',
        'date' => '20/05/2025'
    ],
    [
        'company' => 'GreenFields Ltd.',
        'email' => 'hr@greenfields.com',
        'industry' => 'Agriculture',
        'phone' => '555-234-5678',
        'status' => 'Pending',
        'date' => '18/05/2025'
    ],
    [
        'company' => 'BuildTech Solutions',
        'email' => 'info@buildtech.com',
        'industry' => 'Construction',
        'phone' => '555-345-6789',
        'status' => 'Rejected',
        'date' => '15/05/2025'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Intern Connect | Employer Accounts</title>
  <link rel="stylesheet" href="index.css">
</head>
<body>
  <!-- Background Animation -->
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
    <a href="#" style="margin-left: auto;">Log out</a>
  </nav>

  <div class="content">
    <!-- Admin Name Form using Cookie -->
    <div style="background: white; padding: 15px; margin-bottom: 20px; border-radius: 10px;">
      <h3>Welcome, <?php echo htmlspecialchars($admin_name, ENT_QUOTES); ?>!</h3>
      <form method="post" style="margin-top: 10px;">
        <label for="admin_name">Set Admin Name:</label>
        <input type="text" name="admin_name" id="admin_name" value="<?php echo htmlspecialchars($admin_name, ENT_QUOTES); ?>" required>
        <button type="submit">Update Name</button>
      </form>
    </div>

    <!-- Success Message from Cookie -->
    <?php if ($success_message): ?>
      <div style="background: #10b981; color: white; padding: 15px; margin-bottom: 20px; border-radius: 10px;">
        <?php echo htmlspecialchars($success_message, ENT_QUOTES); ?>
      </div>
    <?php endif; ?>

    <!-- View Preference Form using Cookie -->
    <div style="background: white; padding: 15px; margin-bottom: 20px; border-radius: 10px;">
      <form method="post" style="display: flex; align-items: center; gap: 10px;">
        <label for="view_preference">View as:</label>
        <select name="view_preference" id="view_preference" onchange="this.form.submit()">
          <option value="table" <?php echo $view_preference === 'table' ? 'selected' : ''; ?>>Table View</option>
          <option value="card" <?php echo $view_preference === 'card' ? 'selected' : ''; ?>>Card View</option>
        </select>
      </form>
    </div>

    <h1 class="welcome">Registered Employers (<?php echo count($employers); ?> total)</h1>

    <div class="table-section">
      <?php if ($view_preference === 'table'): ?>
        <!-- Table View -->
        <table class="apps-table">
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
          <tbody>
            <?php foreach ($employers as $employer): ?>
              <tr>
                <td><?php echo htmlspecialchars($employer['company'], ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars($employer['email'], ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars($employer['industry'], ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars($employer['phone'], ENT_QUOTES); ?></td>
                <td>
                  <span class="status-<?php echo strtolower($employer['status']); ?>">
                    <?php echo htmlspecialchars($employer['status'], ENT_QUOTES); ?>
                  </span>
                </td>
                <td><?php echo htmlspecialchars($employer['date'], ENT_QUOTES); ?></td>
                <td>
                  <div class="action-buttons">
                    <?php if ($employer['status'] === 'Pending'): ?>
                      <form method="post" style="display: inline;">
                        <input type="hidden" name="update_status" value="1">
                        <button type="submit" class="btn">Review</button>
                      </form>
                    <?php else: ?>
                      <button type="button" class="btn">View</button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <!-- Card View -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
          <?php foreach ($employers as $employer): ?>
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
              <h3><?php echo htmlspecialchars($employer['company'], ENT_QUOTES); ?></h3>
              <p><strong>Email:</strong> <?php echo htmlspecialchars($employer['email'], ENT_QUOTES); ?></p>
              <p><strong>Industry:</strong> <?php echo htmlspecialchars($employer['industry'], ENT_QUOTES); ?></p>
              <p><strong>Phone:</strong> <?php echo htmlspecialchars($employer['phone'], ENT_QUOTES); ?></p>
              <p><strong>Status:</strong> 
                <span class="status-<?php echo strtolower($employer['status']); ?>">
                  <?php echo htmlspecialchars($employer['status'], ENT_QUOTES); ?>
                </span>
              </p>
              <p><strong>Date:</strong> <?php echo htmlspecialchars($employer['date'], ENT_QUOTES); ?></p>
              <div style="margin-top: 15px;">
                <?php if ($employer['status'] === 'Pending'): ?>
                  <form method="post" style="display: inline;">
                    <input type="hidden" name="update_status" value="1">
                    <button type="submit" class="btn">Review</button>
                  </form>
                <?php else: ?>
                  <button type="button" class="btn">View</button>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <footer class="footer">Intern Connect © 2025 | All Rights Reserved</footer>

  <!-- Review Modal -->
  <div id="reviewModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Review Employer</h2>
        <button class="close" onclick="closeReviewForm()">&times;</button>
      </div>

      <form method="post">
        <div class="form-group">
          <label class="form-label">Company Name</label>
          <input type="text" class="form-input" value="GreenFields Ltd." readonly>
        </div>

        <div class="form-group">
          <label class="form-label">Status</label>
          <select class="form-input" name="status">
            <option value="pending" selected>Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Comments</label>
          <textarea class="form-textarea" name="comments" placeholder="Add your review comments here..."></textarea>
        </div>

        <div class="form-actions">
          <button type="button" class="btn btn-cancel" onclick="closeReviewForm()">Cancel</button>
          <input type="hidden" name="update_status" value="1">
          <button type="submit" class="btn btn-submit">Submit</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openReviewForm() {
      const modal = document.getElementById("reviewModal");
      modal.classList.add("show");
      document.body.style.overflow = "hidden";
    }

    function closeReviewForm() {
      const modal = document.getElementById("reviewModal");
      modal.classList.remove("show");
      document.body.style.overflow = "";
    }

    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        closeReviewForm();
      }
    });
  </script>
</body>
</html>
