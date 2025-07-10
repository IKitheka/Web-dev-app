<?php
session_start();
require_once '../database/connection.php';
require_once '../database/auth_helpers.php';
require_once '../includes/navigation.php';

require_auth('employer');

$employer_id = get_current_user_id();
$internship_id = isset($_GET['internship_id']) ? trim($_GET['internship_id']) : null;
$applications = [];
$internship_title = 'All Internships';
$error_message = '';

try {
    $conn = create_connection();
    
    if ($internship_id) {
        $title_query = 'SELECT title FROM "Internships" WHERE internship_id = $1 AND employer_id = $2';
        $title_result = safe_query($conn, $title_query, [$internship_id, $employer_id]);
        
        if ($title_result && pg_num_rows($title_result) > 0) {
            $internship_title = pg_fetch_assoc($title_result)['title'];
            
            $apps_query = '
                SELECT 
                    a.application_id,
                    a.status,
                    a.application_date,
                    a.cover_letter,
                    a.resume_url,
                    s.name as student_name,
                    s.email as student_email,
                    s.phone as student_phone,
                    s.department,
                    s.academic_year,
                    i.title as internship_title
                FROM "Applications" a
                JOIN "Students" s ON a.student_id = s.student_id
                JOIN "Internships" i ON a.internship_id = i.internship_id
                WHERE a.internship_id = $1 AND i.employer_id = $2
                ORDER BY a.application_date DESC
            ';
            $apps_result = safe_query($conn, $apps_query, [$internship_id, $employer_id]);
        } else {
            $error_message = 'Internship not found or you do not have permission to view it.';
        }
    } else {
        $apps_query = '
            SELECT 
                a.application_id,
                a.status,
                a.application_date,
                a.cover_letter,
                a.resume_url,
                s.name as student_name,
                s.email as student_email,
                s.phone as student_phone,
                s.department,
                s.academic_year,
                i.title as internship_title
            FROM "Applications" a
            JOIN "Students" s ON a.student_id = s.student_id
            JOIN "Internships" i ON a.internship_id = i.internship_id
            WHERE i.employer_id = $1
            ORDER BY a.application_date DESC
        ';
        $apps_result = safe_query($conn, $apps_query, [$employer_id]);
    }
    
    if (isset($apps_result) && $apps_result) {
        while ($row = pg_fetch_assoc($apps_result)) {
            $applications[] = $row;
        }
    }
    
    if ($conn) {
        pg_close($conn);
    }
} catch (Exception $e) {
    error_log("View applicants error: " . $e->getMessage());
    $error_message = 'Error loading applications. Please try again.';
}

$flash = get_flash_message();
$success_message = '';
if ($flash && $flash['type'] === 'success') {
    $success_message = $flash['message'];
} elseif ($flash && $flash['type'] === 'error') {
    $error_message = $flash['message'];
}

$total_apps = count($applications);
$pending_count = count(array_filter($applications, fn($app) => $app['status'] === 'Pending'));
$approved_count = count(array_filter($applications, fn($app) => $app['status'] === 'Approved'));
$rejected_count = count(array_filter($applications, fn($app) => $app['status'] === 'Rejected'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Intern Connect | View Applicants</title>
  <link rel="icon" type="image/x-icon" href="../static/images/title.png">
  <link rel="stylesheet" href="../static/css/index.css">
  <?php echo get_navigation_styles(); ?>
  <style>
    .applicants-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 20px;
    }
    .applicants-header {
      text-align: center;
      margin-bottom: 40px;
    }
    .internship-badge {
      display: inline-block;
      background: rgba(255, 255, 255, 0.1);
      padding: 10px 20px;
      border-radius: 25px;
      font-size: 1.1rem;
      font-weight: 500;
      color: #4ecdc4;
      border: 1px solid rgba(255, 255, 255, 0.2);
      margin-top: 10px;
    }
    .stats-overview {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .stat-card {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      padding: 20px;
      text-align: center;
      border: 1px solid rgba(255, 255, 255, 0.2);
      transition: all 0.3s ease;
    }
    .stat-card:hover {
      transform: translateY(-5px);
      border-color: rgba(255, 255, 255, 0.4);
    }
    .stat-number {
      font-size: 2rem;
      font-weight: bold;
      color: white;
      display: block;
      margin-bottom: 5px;
    }
    .stat-label {
      font-size: 0.9rem;
      opacity: 0.8;
    }
    .bulk-actions {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      padding: 20px;
      margin-bottom: 30px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      display: flex;
      align-items: center;
      gap: 15px;
      flex-wrap: wrap;
    }
    .bulk-actions label {
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 500;
      color: white;
      cursor: pointer;
    }
    .bulk-actions select {
      padding: 8px 12px;
      border-radius: 8px;
      border: 1px solid rgba(255, 255, 255, 0.3);
      background: rgba(255, 255, 255, 0.1);
      color: white;
      font-size: 0.9rem;
      min-width: 150px;
    }
    .bulk-actions select option {
      background: var(--color-blue-900);
      color: white;
    }
    .bulk-btn {
      padding: 8px 16px;
      background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
      border: none;
      border-radius: 20px;
      color: white;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 0.9rem;
    }
    .bulk-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
    }
    .bulk-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }
    .applications-table-container {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      padding: 0;
      border: 1px solid rgba(255, 255, 255, 0.2);
      overflow: hidden;
    }
    .applications-table {
      width: 100%;
      border-collapse: collapse;
      margin: 0;
    }
    .applications-table thead {
      background: rgba(255, 255, 255, 0.1);
    }
    .applications-table th {
      padding: 15px 12px;
      text-align: left;
      font-weight: 600;
      font-size: 0.9rem;
      color: white;
      border-bottom: 2px solid rgba(255, 255, 255, 0.2);
      white-space: nowrap;
    }
    .applications-table td {
      padding: 15px 12px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      color: white;
      font-size: 0.9rem;
      vertical-align: middle;
    }
    .applications-table tbody tr {
      transition: all 0.3s ease;
    }
    .applications-table tbody tr:hover {
      background: rgba(255, 255, 255, 0.05);
    }
    .application-checkbox {
      width: 16px;
      height: 16px;
      cursor: pointer;
    }
    .status-select {
      padding: 6px 10px;
      border-radius: 6px;
      border: 1px solid rgba(255, 255, 255, 0.3);
      background: rgba(255, 255, 255, 0.1);
      color: white;
      font-size: 0.85rem;
      cursor: pointer;
    }
    .status-select option {
      background: var(--color-blue-900);
      color: white;
    }
    .action-btn {
      padding: 6px 12px;
      background: rgba(255, 255, 255, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 15px;
      color: white;
      font-size: 0.8rem;
      cursor: pointer;
      transition: all 0.3s ease;
      margin: 2px;
    }
    .action-btn:hover {
      background: rgba(255, 255, 255, 0.3);
      transform: translateY(-1px);
    }
    .resume-link {
      color: #4ecdc4;
      text-decoration: none;
      font-weight: 500;
      font-size: 0.85rem;
    }
    .resume-link:hover {
      color: #00ff88;
      text-decoration: underline;
    }
    .details-row {
      display: none;
      background: rgba(255, 255, 255, 0.05);
    }
    .details-row.show {
      display: table-row;
    }
    .details-content {
      padding: 20px;
      border-radius: 10px;
      background: rgba(255, 255, 255, 0.08);
      margin: 10px;
    }
    .details-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 15px;
      margin-bottom: 15px;
    }
    .detail-item {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }
    .detail-label {
      font-weight: 600;
      color: #4ecdc4;
      font-size: 0.9rem;
    }
    .detail-value {
      color: white;
      font-size: 0.9rem;
    }
    .cover-letter-section {
      background: rgba(255, 255, 255, 0.1);
      padding: 15px;
      border-radius: 8px;
      max-height: 200px;
      overflow-y: auto;
      line-height: 1.5;
      color: rgba(255, 255, 255, 0.9);
    }
    .no-applications {
      text-align: center;
      padding: 60px 20px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 15px;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .no-applications-icon {
      font-size: 4rem;
      margin-bottom: 20px;
      opacity: 0.6;
    }
    .no-applications h3 {
      font-size: 1.5rem;
      margin-bottom: 10px;
      color: white;
    }
    .no-applications p {
      opacity: 0.8;
      font-size: 1.1rem;
    }
    .loading-spinner {
      display: none;
      text-align: center;
      padding: 20px;
      font-size: 1.1rem;
      color: #4ecdc4;
    }
    @media (max-width: 768px) {
      .bulk-actions {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
      }
      .bulk-actions select,
      .bulk-btn {
        width: 100%;
      }
      .applications-table-container {
        overflow-x: auto;
      }
      .applications-table {
        min-width: 800px;
      }
      .details-grid {
        grid-template-columns: 1fr;
      }
      .stats-overview {
        grid-template-columns: 1fr 1fr;
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

  <?php echo render_page_header('VIEW APPLICANTS', 'Manage internship applications'); ?>
  <?php echo render_navigation('applicants'); ?>
  <div class="content">
    <div class="applicants-container">
      <div class="applicants-header">
        <h1 class="welcome">Application Management</h1>
        <div class="internship-badge">
          <?php echo safe_output($internship_title); ?>
        </div>
      </div>
      <?php if ($success_message): ?>
        <?php echo render_message($success_message, 'success'); ?>
      <?php endif; ?>
      <?php if ($error_message): ?>
        <?php echo render_message($error_message, 'error'); ?>
      <?php endif; ?>
      <div id="message-container"></div>
      <div class="loading-spinner" id="loading">
        ⏳ Processing requests...
      </div>
      <?php if (!empty($applications)): ?>
        <div class="stats-overview">
          <div class="stat-card">
            <span class="stat-number"><?php echo $total_apps; ?></span>
            <div class="stat-label">Total Applications</div>
          </div>
          <div class="stat-card">
            <span class="stat-number"><?php echo $pending_count; ?></span>
            <div class="stat-label">Pending Review</div>
          </div>
          <div class="stat-card">
            <span class="stat-number"><?php echo $approved_count; ?></span>
            <div class="stat-label">Approved</div>
          </div>
          <div class="stat-card">
            <span class="stat-number"><?php echo $rejected_count; ?></span>
            <div class="stat-label">Rejected</div>
          </div>
        </div>
        <div class="bulk-actions">
          <label>
            <input type="checkbox" id="select-all" class="application-checkbox">
            <span>Select All Applications</span>
          </label>
          <select id="bulk-status">
            <option value="">Choose Action...</option>
            <option value="Approved">Approve Selected</option>
            <option value="Rejected">Reject Selected</option>
            <option value="Pending">Mark as Pending</option>
          </select>
          <button class="bulk-btn" onclick="performBulkAction()" id="bulk-apply-btn">
            Apply Action
          </button>
        </div>
        <div class="applications-table-container">
          <table class="applications-table">
            <thead>
              <tr>
                <th><input type="checkbox" id="header-select-all" class="application-checkbox"></th>
                <th>Student</th>
                <th>Email</th>
                <th>Department</th>
                <th>Year</th>
                <th>Status</th>
                <th>Applied</th>
                <th>Resume</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($applications as $app): ?>
                <tr data-application-id="<?php echo safe_output($app['application_id']); ?>">
                  <td>
                    <input type="checkbox" class="app-checkbox application-checkbox" 
                           value="<?php echo safe_output($app['application_id']); ?>">
                  </td>
                  <td>
                    <strong><?php echo safe_output($app['student_name']); ?></strong>
                    <?php if (!empty($app['student_phone'])): ?>
                      <br><small style="opacity: 0.8;">Phone: <?php echo safe_output($app['student_phone']); ?></small>
                    <?php endif; ?>
                  </td>
                  <td><?php echo safe_output($app['student_email']); ?></td>
                  <td><?php echo safe_output($app['department']); ?></td>
                  <td><?php echo safe_output($app['academic_year']); ?></td>
                  <td>
                    <span class="status-<?php echo strtolower($app['status']); ?>">
                      <?php echo safe_output($app['status']); ?>
                    </span>
                  </td>
                  <td><?php echo date('M d, Y', strtotime($app['application_date'])); ?></td>
                  <td>
                    <?php if (!empty($app['resume_url'])): ?>
                      <a href="<?php echo safe_output($app['resume_url']); ?>" 
                         class="resume-link" target="_blank" rel="noopener">
                        📄 Download
                      </a>
                    <?php else: ?>
                      <span style="opacity: 0.6;">No Resume</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <button class="action-btn" 
                            onclick="toggleDetails('<?php echo safe_output($app['application_id']); ?>')">
                      Details
                    </button>
                    <select class="status-select" 
                            onchange="updateStatus('<?php echo safe_output($app['application_id']); ?>', this.value)">
                      <option value="">Change Status...</option>
                      <option value="Pending" <?php echo $app['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                      <option value="Approved" <?php echo $app['status'] === 'Approved' ? 'selected' : ''; ?>>Approve</option>
                      <option value="Rejected" <?php echo $app['status'] === 'Rejected' ? 'selected' : ''; ?>>Reject</option>
                    </select>
                  </td>
                </tr>
                <tr class="details-row" id="details-<?php echo safe_output($app['application_id']); ?>">
                  <td colspan="9">
                    <div class="details-content">
                      <h4 style="color: #4ecdc4; margin-bottom: 15px;">Application Details</h4>
                      <div class="details-grid">
                        <div class="detail-item">
                            <span class="detail-label">Student Name</span>
                            <span class="detail-value"><?php echo safe_output($app['student_name']); ?></span>
                          </div>
                          <div class="detail-item">
                            <span class="detail-label">Email Address</span>
                            <span class="detail-value"><?php echo safe_output($app['student_email']); ?></span>
                          </div>
                          <div class="detail-item">
                            <span class="detail-label">Phone Number</span>
                            <span class="detail-value"><?php echo safe_output($app['student_phone'] ?: 'Not provided'); ?></span>
                          </div>
                          <div class="detail-item">
                            <span class="detail-label">Academic Program</span>
                            <span class="detail-value"><?php echo safe_output($app['department']); ?> - <?php echo safe_output($app['academic_year']); ?></span>
                          </div>
                          <div class="detail-item">
                            <span class="detail-label">Position Applied</span>
                            <span class="detail-value"><?php echo safe_output($app['internship_title']); ?></span>
                          </div>
                          <div class="detail-item">
                            <span class="detail-label">Application Date</span>
                            <span class="detail-value"><?php echo date('F j, Y \a\t g:i A', strtotime($app['application_date'])); ?></span>
                          </div>
                        </div>
                        <div style="margin-top: 20px;">
                        <span class="detail-label">Cover Letter</span>
                          <div class="cover-letter-section">
                            <?php if (!empty($app['cover_letter'])): ?>
                              <?php echo nl2br(safe_output($app['cover_letter'])); ?>
                            <?php else: ?>
                              <em style="opacity: 0.7;">No cover letter provided by the applicant.</em>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </td>
                  </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="no-applications">
          <div class="no-applications-icon"></div>
          <h3>No Applications Found</h3>
          <p>
            <?php if ($internship_id): ?>
              This internship hasn't received any applications yet. Share the position to attract qualified candidates!
            <?php else: ?>
              You haven't received any applications across your internship postings yet. Consider promoting your opportunities to reach more students.
            <?php endif; ?>
          </p>
          <a href="../Forms/create_job.php" class="btn" style="margin-top: 20px;">
            Post New Internship
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <footer class="footer">
    Intern Connect &copy; 2025 | All Rights Reserved
  </footer>
  <script>
    const employerId = '<?php echo safe_output($employer_id); ?>';
    document.getElementById('select-all')?.addEventListener('change', function() {
      const checkboxes = document.querySelectorAll('.app-checkbox');
      checkboxes.forEach(cb => cb.checked = this.checked);
      document.getElementById('header-select-all').checked = this.checked;
    });
    document.getElementById('header-select-all')?.addEventListener('change', function() {
      const checkboxes = document.querySelectorAll('.app-checkbox');
      checkboxes.forEach(cb => cb.checked = this.checked);
      document.getElementById('select-all').checked = this.checked;
    });
    function updateStatus(applicationId, newStatus) {
      if (!newStatus) return;
      showLoading(true);
      const formData = new FormData();
      formData.append('application_id', applicationId);
      formData.append('status', newStatus);
      fetch('update_appstatus.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(text => {
        showLoading(false);
        if (text.toLowerCase().includes('success')) {
          showMessage(text, 'success');
          updateRowStatus(applicationId, newStatus);
          updateStats();
        } else {
          showMessage(text, 'error');
        }
      })
      .catch(error => {
        showLoading(false);
        showMessage('Error updating status: ' + error.message, 'error');
        console.error('Update status error:', error);
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
        showMessage('Please select an action to perform', 'error');
        return;
      }
      if (!confirm(`Are you sure you want to ${newStatus.toLowerCase()} ${selectedApps.length} application(s)?`)) {
        return;
      }
      showLoading(true);
      const bulkBtn = document.getElementById('bulk-apply-btn');
      bulkBtn.disabled = true;
      bulkBtn.textContent = '⏳ Processing...';
      const formData = new FormData();
      selectedApps.forEach(id => formData.append('application_ids[]', id));
      formData.append('status', newStatus);
      fetch('update_appstatus.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(text => {
        showLoading(false);
        bulkBtn.disabled = false;
        bulkBtn.textContent = 'Apply Action';
        if (text.toLowerCase().includes('updated')) {
          showMessage(text, 'success');
          selectedApps.forEach(appId => updateRowStatus(appId, newStatus));
          document.getElementById('select-all').checked = false;
          document.getElementById('header-select-all').checked = false;
          document.querySelectorAll('.app-checkbox').forEach(cb => cb.checked = false);
          document.getElementById('bulk-status').value = '';
          updateStats();
        } else {
          showMessage(text, 'error');
        }
      })
      .catch(error => {
        showLoading(false);
        bulkBtn.disabled = false;
        bulkBtn.textContent = 'Apply Action';
        showMessage('Error updating applications: ' + error.message, 'error');
        console.error('Bulk update error:', error);
      });
    }
    function toggleDetails(applicationId) {
      const detailsRow = document.getElementById('details-' + applicationId);
      if (detailsRow) {
        detailsRow.classList.toggle('show');
        const button = document.querySelector(`button[onclick*="${applicationId}"]`);
        if (button && detailsRow.classList.contains('show')) {
          button.innerHTML = '🔽 Hide';
        } else if (button) {
          button.innerHTML = '👁️ Details';
        }
      }
    }
    function updateRowStatus(applicationId, newStatus) {
      const row = document.querySelector(`tr[data-application-id="${applicationId}"]`);
      if (row) {
        const statusCell = row.querySelector('[class*="status-"]');
        if (statusCell) {
          statusCell.className = 'status-' + newStatus.toLowerCase();
          statusCell.textContent = newStatus;
        }
        const statusSelect = row.querySelector('.status-select');
        if (statusSelect) {
          Array.from(statusSelect.options).forEach(option => {
            option.selected = option.value === newStatus;
          });
        }
      }
    }
    function updateStats() {
      const rows = document.querySelectorAll('tr[data-application-id]');
      let pendingCount = 0;
      let approvedCount = 0;
      let rejectedCount = 0;
      rows.forEach(row => {
        const statusElement = row.querySelector('[class*="status-"]');
        if (statusElement) {
          const status = statusElement.textContent.trim();
          if (status === 'Pending') pendingCount++;
          else if (status === 'Approved') approvedCount++;
          else if (status === 'Rejected') rejectedCount++;
        }
      });
      const statCards = document.querySelectorAll('.stat-card .stat-number');
      if (statCards.length >= 4) {
        statCards[1].textContent = pendingCount;
        statCards[2].textContent = approvedCount;
        statCards[3].textContent = rejectedCount;
      }
    }
    function showMessage(message, type) {
      const container = document.getElementById('message-container');
      const messageHtml = `
        <div class="message message-${type}" style="
          padding: 15px;
          border-radius: 8px;
          margin: 15px auto;
          font-weight: 500;
          display: flex;
          align-items: center;
          gap: 10px;
          max-width: 1200px;
          animation: messageSlideIn 0.3s ease-out;
          ${type === 'success' ? 'background: rgba(0, 255, 0, 0.1); color: #00ff88; border: 1px solid rgba(0, 255, 0, 0.3);' : ''}
          ${type === 'error' ? 'background: rgba(255, 0, 0, 0.1); color: #ff6b6b; border: 1px solid rgba(255, 0, 0, 0.3);' : ''}
        ">
          <span>${type === 'success' ? '✅' : '❌'}</span>
          <span>${message}</span>
        </div>
      `;
      container.innerHTML = messageHtml;
      setTimeout(() => {
        container.innerHTML = '';
      }, 5000);
    }
    function showLoading(show) {
      const loadingElement = document.getElementById('loading');
      if (loadingElement) {
        loadingElement.style.display = show ? 'block' : 'none';
      }
    }
    document.addEventListener('DOMContentLoaded', function() {
      document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'a' && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
          e.preventDefault();
          const selectAllCheckbox = document.getElementById('select-all');
          if (selectAllCheckbox) {
            selectAllCheckbox.checked = !selectAllCheckbox.checked;
            selectAllCheckbox.dispatchEvent(new Event('change'));
          }
        }
      });
      document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
          if (this.value) {
            const row = this.closest('tr');
            const appId = row?.getAttribute('data-application-id');
            if (appId) {
              updateStatus(appId, this.value);
            }
          }
        });
      });
      document.querySelectorAll('.action-btn').forEach(btn => {
        btn.addEventListener('mouseenter', function() {
          this.style.transform = 'translateY(-2px)';
        });
        btn.addEventListener('mouseleave', function() {
          this.style.transform = 'translateY(0)';
        });
      });
      console.log('View Applicants page loaded with', document.querySelectorAll('tr[data-application-id]').length, 'applications');
    });
  </script>
</body>
</html>
