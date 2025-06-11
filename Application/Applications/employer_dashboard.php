<?php
session_start();

$_SESSION['employer_id'] = '558a9b1b-f900-425c-a681-0af49fba4879';

require_once '../database/connection.php';
require_once 'get_applications.php';
require_once 'update_appstatus.php';

/*
if (!isset($_SESSION['employer_id'])) {
    header('Location: login.php');
    exit;
}
*/


$employer_id = $_SESSION['employer_id'];

$conn1 = create_connection();
$employer_query = "SELECT * FROM Employers WHERE employer_id = $1";
$employer_result = pg_query_params($conn1, $employer_query, array($employer_id));
$employer = pg_fetch_assoc($employer_result);

$stats = get_application_statistics($employer_id);

$conn2 = create_connection();
$jobs_query = "SELECT COUNT(*) as total FROM Internships WHERE employer_id = $1";
$jobs_result = pg_query_params($conn2, $jobs_query, array($employer_id));
$jobs_count = pg_fetch_assoc($jobs_result)['total'];

$shortlisted_count = $stats['Approved'];

$interviews_query = "
    SELECT COUNT(*) as total 
    FROM Applications a
    INNER JOIN Internships i ON a.internship_id = i.internship_id
    WHERE i.employer_id = $1 AND a.status = 'Approved'
";

$conn4 = create_connection();
$interviews_result = pg_query_params($conn4, $interviews_query, array($employer_id));
$interviews_count = pg_fetch_assoc($interviews_result)['total'];

$recent_apps_query = "
    SELECT 
        a.application_id,
        a.application_date,
        a.status,
        s.name as student_name,
        s.email as student_email,
        i.title as internship_title
    FROM Applications a
    INNER JOIN Students s ON a.student_id = s.student_id
    INNER JOIN Internships i ON a.internship_id = i.internship_id
    WHERE i.employer_id = $1
    ORDER BY a.application_date DESC
    LIMIT 4
";

$conn3 = create_connection();
$recent_apps_result = pg_query_params($conn3, $recent_apps_query, array($employer_id));
$recent_applications = array();
while ($row = pg_fetch_assoc($recent_apps_result)) {
    $recent_applications[] = $row;
}

pg_close($conn3);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Intern Connect | Employer Dashboard</title>
  <link rel="icon" type="image/x-icon" href="/static/images/title.png">
  <link rel="stylesheet" href="../static/css/index.css">
</head>
<body>
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

  <header class="header">
    EMPLOYER DASHBOARD
  </header>

  <nav class="nav">
    <a href="#">INTERN CONNECT</a>
    <a href="#" class="active">Dashboard</a>
    <a href="post_job.php">Post a Job</a>
    <a href="manage_listings.php">Manage Listings</a>
    <a href="view_applicants.php">Applicants</a>
    <a href="profile.php">Profile</a>
    <a href="logout.php" style="margin-left: auto;">Log out</a>
  </nav>

  <div class="content">
    <h1 class="welcome">Welcome, <?php echo htmlspecialchars($employer['company_name']); ?></h1>

    <div class="stats-container">
      <div class="stat-card">
        <div class="stat-number"><?php echo $jobs_count; ?></div>
        <div class="stat-label">Jobs Posted</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-number"><?php echo $stats['Total']; ?></div>
        <div class="stat-label">Applications Received</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-number"><?php echo $shortlisted_count; ?></div>
        <div class="stat-label">Shortlisted</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-number"><?php echo $stats['Pending']; ?></div>
        <div class="stat-label">Pending Review</div>
      </div>
    </div>

    <div class="recent-apps">
      <h2>Recent Applicants</h2>
      <?php if (count($recent_applications) > 0): ?>
        <table class="apps-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Position</th>
              <th>Status</th>
              <th>Date Applied</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent_applications as $app): ?>
              <tr>
                <td><?php echo htmlspecialchars($app['student_name']); ?></td>
                <td><?php echo htmlspecialchars($app['internship_title']); ?></td>
                <td>
                  <?php
                  $status_class = '';
                  $status_text = $app['status'];
                  
                  switch ($app['status']) {
                    case 'Approved':
                      $status_class = 'status-shortlisted';
                      $status_text = 'Shortlisted';
                      break;
                    case 'Rejected':
                      $status_class = 'status-rejected';
                      $status_text = 'Rejected';
                      break;
                    case 'Pending':
                    default:
                      $status_class = 'status-review';
                      $status_text = 'Under Review';
                      break;
                  }
                  ?>
                  <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                </td>
                <td><?php echo date('d/m/Y', strtotime($app['application_date'])); ?></td>
                <td>
                  <button class="btn" onclick="quickReview('<?php echo $app['application_id']; ?>', '<?php echo htmlspecialchars($app['student_name']); ?>', '<?php echo $app['status']; ?>')">
                    <?php echo $app['status'] === 'Pending' ? 'Review' : 'Update'; ?>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        
        <div style="text-align: center; margin-top: 20px;">
          <a href="view_applicants.php" class="btn" style="background: #4f46e5; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px;">View All Applications</a>
        </div>
      <?php else: ?>
        <p>No applications received yet. <a href="post_job.php">Post your first internship</a> to start receiving applications.</p>
      <?php endif; ?>
    </div>
  </div>

  <footer class="footer">
    Intern Connect &copy; 2025 | All Rights Reserved
  </footer>

  <div id="quickReviewModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; min-width: 300px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
      <h3 style="margin-top: 0; color: #333;">Quick Application Review</h3>
      <p id="modalStudentInfo" style="margin: 15px 0; color: #666;"></p>
      <div style="margin: 20px 0;">
        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Update Status:</label>
        <select id="modalStatusSelect" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
          <option value="Pending">Under Review</option>
          <option value="Approved">Shortlist</option>
          <option value="Rejected">Reject</option>
        </select>
      </div>
      <div style="text-align: right; margin-top: 25px;">
        <button onclick="closeModal()" style="margin-right: 10px; padding: 8px 16px; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 5px; cursor: pointer;">Cancel</button>
        <button onclick="confirmStatusUpdate()" class="btn" style="background: #4f46e5; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer;">Update Status</button>
      </div>
    </div>
  </div>

  <div id="loadingIndicator" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); z-index: 1001;">
    <p>Updating application status...</p>
  </div>

  <script>
    const employerId = '<?php echo $employer_id; ?>';
    let currentApplicationId = null;

    function quickReview(applicationId, studentName, currentStatus) {
      currentApplicationId = applicationId;
      
      document.getElementById('modalStudentInfo').textContent = `Student: ${studentName}`;
      document.getElementById('modalStatusSelect').value = currentStatus;
      document.getElementById('quickReviewModal').style.display = 'block';
    }

    function closeModal() {
      document.getElementById('quickReviewModal').style.display = 'none';
      currentApplicationId = null;
    }

    function confirmStatusUpdate() {
      const newStatus = document.getElementById('modalStatusSelect').value;
      
      if (!currentApplicationId || !newStatus) {
        return;
      }

      document.getElementById('loadingIndicator').style.display = 'block';
      closeModal();

      fetch('update_application_status.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          application_id: currentApplicationId,
          status: newStatus,
          employer_id: employerId
        })
      })
      .then(response => response.json())
      .then(data => {
        document.getElementById('loadingIndicator').style.display = 'none';
        
        if (data.success) {
          location.reload();
        } else {
          alert('Failed to update status: ' + (data.message || 'Unknown error'));
        }
      })
      .catch(error => {
        document.getElementById('loadingIndicator').style.display = 'none';
        alert('Error updating status: ' + error.message);
      });
    }

    document.getElementById('quickReviewModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeModal();
      }
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeModal();
      }
    });
  </script>
</body>
</html>