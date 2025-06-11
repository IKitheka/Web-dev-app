<?php

session_start();
$_SESSION['employer_id'] = '558a9b1b-f900-425c-a681-0af49fba4879';

require_once '../database/connection.php';
require_once 'get_applications.php';

/*
if (!isset($_SESSION['employer_id'])) {
    header('Location: login.php');
    exit;
}
*/


$employer_id = $_SESSION['employer_id'];
$internship_id = isset($_GET['internship_id']) ? $_GET['internship_id'] : null;

if ($internship_id) {
    $applications = get_internship_applications($internship_id);
    
    $conn = create_connection();
    $title_query = "SELECT title FROM Internships WHERE internship_id = $1 AND employer_id = $2";
    $title_result = pg_query_params($conn, $title_query, array($internship_id, $employer_id));
    $internship_title = $title_result ? pg_fetch_assoc($title_result)['title'] : 'Unknown Internship';
    pg_close($conn);
} else {
    $applications = get_employer_applications($employer_id);
    $internship_title = 'All Internships';
}

if ($applications === false) {
    $applications = array();
    $error_message = "Failed to load applications.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Intern Connect | View Applicants</title>
  <link rel="icon" href="/static/images/title.png">
  <link rel="stylesheet" href="/static/css/index.css">
  <style>
    .loading { display: none; text-align: center; margin: 20px 0; }
    .error { color: #e74c3c; background: #fadbd8; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .success { color: #27ae60; background: #d5f4e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .bulk-actions { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
    .bulk-actions select, .bulk-actions button { margin: 0 10px; }
    .application-details { display: none; background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
  </style>
</head>
<body>
  <svg class="bg-animation" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" preserveAspectRatio="none">
    <path fill="url(#aurora-gradient)" d="M0,128L60,138.7C120,149,240,171,360,154.7C480,139,600,85,720,96C840,107,960,181,1080,197.3C1200,213,1320,171,1380,149.3L1440,128L1440,0L1380,0C1320,0,1200,0,1080,0C960,0,840,0,720,0C600,0,480,0,360,0C240,0,120,0,60,0L0,0Z"></path>
    <defs>
      <linearGradient id="aurora-gradient" x1="0" y1="0" x2="1" y2="1">
        <stop offset="0%" stop-color="#4f46e5"/>
        <stop offset="50%" stop-color="#06b6d4"/>
        <stop offset="100%" stop-color="#8b5cf6"/>
      </linearGradient>
    </defs>
  </svg>

  <header class="header">VIEW APPLICANTS</header>
  <nav class="nav">
    <a href="#">INTERN CONNECT</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="post_job.php">Post a Job</a>
    <a href="manage_listings.php">Manage Listings</a>
    <a class="active" href="#">Applicants</a>
    <a href="profile.php">Profile</a>
    <a href="logout.php" style="margin-left: auto;">Log out</a>
  </nav>

  <div class="content">
    <h1 class="welcome">Applicants for "<?php echo htmlspecialchars($internship_title); ?>"</h1>
    
    <?php if (isset($error_message)): ?>
      <div class="error"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div id="message-container"></div>
    <div class="loading" id="loading">Processing...</div>

    <?php if (count($applications) > 0): ?>
      <div class="bulk-actions">
        <label>
          <input type="checkbox" id="select-all"> Select All
        </label>
        <select id="bulk-status">
          <option value="">Choose Action</option>
          <option value="Approved">Approve Selected</option>
          <option value="Rejected">Reject Selected</option>
          <option value="Pending">Mark as Pending</option>
        </select>
        <button class="btn" onclick="performBulkAction()">Apply Action</button>
      </div>

      <div class="table-section">
        <table class="apps-table">
          <thead>
            <tr>
              <th><input type="checkbox" id="header-select-all"></th>
              <th>Name</th>
              <th>Email</th>
              <th>Department</th>
              <th>Academic Year</th>
              <th>Status</th>
              <th>Applied Date</th>
              <th>Resume</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($applications as $app): ?>
              <tr data-application-id="<?php echo $app['application_id']; ?>">
                <td><input type="checkbox" class="app-checkbox" value="<?php echo $app['application_id']; ?>"></td>
                <td><?php echo htmlspecialchars($app['student_name']); ?></td>
                <td><?php echo htmlspecialchars($app['student_email']); ?></td>
                <td><?php echo htmlspecialchars($app['department']); ?></td>
                <td><?php echo htmlspecialchars($app['academic_year']); ?></td>
                <td>
                  <span class="status-<?php echo strtolower($app['status']); ?>">
                    <?php echo htmlspecialchars($app['status']); ?>
                  </span>
                </td>
                <td><?php echo date('d/m/Y', strtotime($app['application_date'])); ?></td>
                <td>
                  <?php if ($app['resume_url']): ?>
                    <a href="<?php echo htmlspecialchars($app['resume_url']); ?>" class="btn" target="_blank">Download</a>
                  <?php else: ?>
                    <span>No Resume</span>
                  <?php endif; ?>
                </td>
                <td>
                  <button class="btn" onclick="toggleDetails('<?php echo $app['application_id']; ?>')">Details</button>
                  <select onchange="updateStatus('<?php echo $app['application_id']; ?>', this.value)">
                    <option value="">Change Status</option>
                    <option value="Pending" <?php echo $app['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Approved" <?php echo $app['status'] === 'Approved' ? 'selected' : ''; ?>>Approve</option>
                    <option value="Rejected" <?php echo $app['status'] === 'Rejected' ? 'selected' : ''; ?>>Reject</option>
                  </select>
                </td>
              </tr>
              <tr class="application-details" id="details-<?php echo $app['application_id']; ?>">
                <td colspan="9">
                  <h4>Application Details</h4>
                  <p><strong>Student:</strong> <?php echo htmlspecialchars($app['student_name']); ?></p>
                  <p><strong>Phone:</strong> <?php echo htmlspecialchars($app['student_phone'] ?? 'Not provided'); ?></p>
                  <p><strong>Position:</strong> <?php echo htmlspecialchars($app['internship_title']); ?></p>
                  <p><strong>Cover Letter:</strong></p>
                  <div style="background: white; padding: 10px; border-radius: 3px; max-height: 200px; overflow-y: auto;">
                    <?php echo $app['cover_letter'] ? nl2br(htmlspecialchars($app['cover_letter'])) : 'No cover letter provided'; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="table-section">
        <p>No applications found for this internship.</p>
      </div>
    <?php endif; ?>
  </div>

  <footer class="footer">Intern Connect &copy; 2025 | All Rights Reserved</footer>

  <script>
    const employerId = '<?php echo $employer_id; ?>';

    document.getElementById('select-all').addEventListener('change', function() {
      const checkboxes = document.querySelectorAll('.app-checkbox');
      checkboxes.forEach(cb => cb.checked = this.checked);
    });

    document.getElementById('header-select-all').addEventListener('change', function() {
      const checkboxes = document.querySelectorAll('.app-checkbox');
      checkboxes.forEach(cb => cb.checked = this.checked);
      document.getElementById('select-all').checked = this.checked;
    });

    function updateStatus(applicationId, newStatus) {
      if (!newStatus) return;

      showLoading(true);
      
      fetch('update_application_status.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          application_id: applicationId,
          status: newStatus,
          employer_id: employerId
        })
      })
      .then(response => response.json())
      .then(data => {
        showLoading(false);
        if (data.success) {
          showMessage(data.message, 'success');
          updateRowStatus(applicationId, newStatus);
        } else {
          showMessage(data.message || 'Failed to update status', 'error');
        }
      })
      .catch(error => {
        showLoading(false);
        showMessage('Error updating status: ' + error.message, 'error');
      });
    }

    function performBulkAction() {
      const selectedApps = Array.from(document.querySelectorAll('.app-checkbox:checked')).map(cb => cb.value);
      const newStatus = document.getElementById('bulk-status').value;

      if (selectedApps.length === 0) {
        showMessage('Please select at least one application', 'error');
        return;
      }

      if (!newStatus) {
        showMessage('Please select an action', 'error');
        return;
      }

      showLoading(true);

      fetch('update_application_status.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          application_ids: selectedApps,
          status: newStatus,
          employer_id: employerId
        })
      })
      .then(response => response.json())
      .then(data => {
        showLoading(false);
        if (data.success) {
          showMessage(data.message, 'success');
          selectedApps.forEach(appId => updateRowStatus(appId, newStatus));
          document.getElementById('select-all').checked = false;
          document.getElementById('header-select-all').checked = false;
        } else {
          showMessage(data.message || 'Failed to update applications', 'error');
        }
      })
      .catch(error => {
        showLoading(false);
        showMessage('Error updating applications: ' + error.message, 'error');
      });
    }

    function toggleDetails(applicationId) {
      const detailsRow = document.getElementById('details-' + applicationId);
      if (detailsRow.style.display === 'none' || !detailsRow.style.display) {
        detailsRow.style.display = 'table-row';
      } else {
        detailsRow.style.display = 'none';
      }
    }

    function updateRowStatus(applicationId, newStatus) {
      const row = document.querySelector(`tr[data-application-id="${applicationId}"]`);
      if (row) {
        const statusCell = row.querySelector('.status-pending, .status-approved, .status-rejected');
        if (statusCell) {
          statusCell.className = 'status-' + newStatus.toLowerCase();
          statusCell.textContent = newStatus;
        }
        
        const statusSelect = row.querySelector('select');
        if (statusSelect) {
          statusSelect.value = newStatus;
        }
      }
    }

    function showMessage(message, type) {
      const container = document.getElementById('message-container');
      container.innerHTML = `<div class="${type}">${message}</div>`;
      setTimeout(() => {
        container.innerHTML = '';
      }, 5000);
    }

    function showLoading(show) {
      document.getElementById('loading').style.display = show ? 'block' : 'none';
    }
  </script>
</body>
</html>