<?php
session_start();
require_once '../database/connection.php';
require_once '../database/auth_helpers.php';
require_once '../includes/navigation.php';
if (!validate_user_session()) {
    set_flash_message('Please log in to browse internships.', 'error');
    header('Location: ../Authentication/login.php');
    exit;
}
$conn = create_connection();
if (!$conn) {
    set_flash_message('Database connection failed. Please try again later.', 'error');
    header('Location: ../../index.php');
    exit;
}
$job_title = trim($_GET['job_title'] ?? '');
$company_name = trim($_GET['company_name'] ?? '');
$location = $_GET['location'] ?? 'all';
$duration = $_GET['duration'] ?? 'all';
$industry = $_GET['industry'] ?? 'all';
$location = ($location === 'all') ? 'all' : $location;
$duration = ($duration === 'all') ? 'all' : $duration;
$industry = ($industry === 'all') ? 'all' : $industry;
$query = '
  SELECT i.title, e.company_name, i.location, i.duration, 
         i.start_date, i.end_date, i.requirements, i.internship_id
  FROM "Internships" i
  JOIN "Employers" e ON i.employer_id = e.employer_id
  WHERE i.is_active = TRUE
    AND NOT EXISTS (
      SELECT 1 FROM "Applications" a
      WHERE a.internship_id = i.internship_id AND a.status = \'Approved\'
    )
';
$params = array();
$param_count = 0;
if (!empty($job_title)) {
    $param_count++;
    $query .= " AND i.title ILIKE $" . $param_count;
    $params[] = '%' . $job_title . '%';
}
if (!empty($company_name)) {
    $param_count++;
    $query .= " AND e.company_name ILIKE $" . $param_count;
    $params[] = '%' . $company_name . '%';
}
if ($location !== 'all') {
    $param_count++;
    $query .= " AND i.location = $" . $param_count;
    $params[] = $location;
}
if ($duration !== 'all') {
    $param_count++;
    $query .= " AND i.duration = $" . $param_count;
    $params[] = $duration;
}
if ($industry !== 'all') {
    $param_count++;
    $query .= " AND e.industry = $" . $param_count;
    $params[] = $industry;
}
$query .= " ORDER BY i.posted_at DESC";
$result = safe_query($conn, $query, $params);
if (!$result) {
    set_flash_message('Error loading internships. Please try again.', 'error');
    $result = false;
}
$locations_result = safe_query($conn, 'SELECT DISTINCT location FROM "Internships" WHERE is_active = TRUE AND location IS NOT NULL ORDER BY location');
$durations_result = safe_query($conn, 'SELECT DISTINCT duration FROM "Internships" WHERE is_active = TRUE AND duration IS NOT NULL ORDER BY duration');
$industries_result = safe_query($conn, 'SELECT DISTINCT industry FROM "Employers" ORDER BY industry');
$locations_options = [];
$durations_options = [];
$industries_options = [];
if ($locations_result) {
    while ($row = pg_fetch_assoc($locations_result)) {
        $locations_options[] = $row['location'];
    }
}
if ($durations_result) {
    while ($row = pg_fetch_assoc($durations_result)) {
        $durations_options[] = $row['duration'];
    }
}
if ($industries_result) {
    while ($row = pg_fetch_assoc($industries_result)) {
        $industries_options[] = $row['industry'];
    }
}
$flash = get_flash_message();
$error_message = '';
if ($flash && $flash['type'] === 'error') {
    $error_message = $flash['message'];
}
$applied_ids = [];
if (get_current_user_type() === 'student') {
    $student_id = get_current_user_id();
    if ($student_id) {
        $applied_result = safe_query($conn, 'SELECT internship_id FROM "Applications" WHERE student_id = $1', [$student_id]);
        if ($applied_result) {
            while ($row = pg_fetch_assoc($applied_result)) {
                $applied_ids[] = $row['internship_id'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Intern Connect | Browse Internships</title>
  <link rel="icon" type="image/x-icon" href="../static/images/title.png">
  <link rel="stylesheet" href="../static/css/index.css">
  <?php echo get_navigation_styles(); ?>
  <style>
    .filter-section {
      max-width: 1200px;
      margin: 0 auto 30px;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      padding: 25px;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .filter-form {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 20px;
      align-items: end;
    }
    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
      min-width: 150px;
    }
    .filter-group label {
      font-size: 0.9rem;
      font-weight: 500;
      opacity: 0.9;
      color: white;
    }
    .filter-group select,
    .filter-group input {
      padding: 10px 15px;
      border: none;
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.9);
      color: #333;
      font-size: 0.9rem;
      transition: all 0.3s ease;
    }
    .filter-group select {
      cursor: pointer;
    }
    .filter-group input {
      border: 2px solid transparent;
    }
    .filter-group input:focus {
      outline: none;
      border-color: rgba(255, 99, 132, 0.8);
      background: rgba(255, 255, 255, 1);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .filter-group select:hover,
    .filter-group input:hover {
      background: rgba(255, 255, 255, 1);
      transform: translateY(-2px);
    }
    .search-btn {
      padding: 12px 25px;
      background: rgba(255, 255, 255, 0.2);
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 25px;
      color: white;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
      font-size: 0.9rem;
      grid-column: span 2;
      justify-self: center;
      min-width: 150px;
    }
    .search-btn:hover {
      background: rgba(255, 255, 255, 0.3);
      border-color: rgba(255, 255, 255, 0.5);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .table-container {
      max-width: 1200px;
      margin: 0 auto;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      overflow: hidden;
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    }
    .internship-table {
      width: 100%;
      border-collapse: collapse;
    }
    .internship-table thead {
      background: rgba(255, 255, 255, 0.1);
    }
    .internship-table th {
      padding: 20px 15px;
      text-align: left;
      font-weight: 600;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      border-bottom: 2px solid rgba(255, 255, 255, 0.2);
      color: white;
    }
    .internship-table td {
      padding: 18px 15px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      font-size: 0.9rem;
      line-height: 1.4;
      color: white;
    }
    .internship-table tbody tr {
      transition: all 0.3s ease;
      border: 2px solid transparent;
    }
    .internship-table tbody tr:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: rgba(255, 99, 132, 0.6);
      transform: scale(1.01);
    }
    .apply-btn {
      padding: 8px 16px;
      background: rgba(255, 99, 132, 0.8);
      border: none;
      border-radius: 20px;
      color: white;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
    }
    .apply-btn:hover {
      background: rgba(255, 99, 132, 1);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 99, 132, 0.3);
    }
    .no-results {
      text-align: center;
      padding: 40px;
      font-size: 1.1rem;
      opacity: 0.8;
      color: white;
    }
    .filter-summary {
      background: rgba(255, 255, 255, 0.05);
      padding: 10px 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 0.9rem;
      max-width: 1200px;
      margin-left: auto;
      margin-right: auto;
    }
    @media (max-width: 768px) {
      .filter-form {
        grid-template-columns: 1fr;
        gap: 15px;
      }
      .search-btn {
        grid-column: span 1;
        justify-self: stretch;
      }
      .table-container {
        overflow-x: auto;
      }
      .internship-table {
        min-width: 800px;
      }
    }
    @media (min-width: 1000px) {
      .search-btn {
        grid-column: span 1;
        justify-self: end;
      }
    }
  </style>
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
  <?php echo render_page_header('BROWSE INTERNSHIPS', 'Discover amazing internship opportunities'); ?>
  <?php echo render_navigation('browse_internship'); ?>
  <div class="content">
    <h1 class="welcome">Available Internship Opportunities</h1>
    <?php if ($error_message): ?>
      <?php echo render_message($error_message, 'error'); ?>
    <?php endif; ?>
    <?php if (!empty($job_title) || !empty($company_name) || $location !== 'all' || $duration !== 'all' || $industry !== 'all'): ?>
    <div class="filter-summary">
      <strong>🔍 Active Filters:</strong>
      <?php if (!empty($job_title)): ?>
        <span>Title: "<?php echo safe_output($job_title); ?>"</span>
      <?php endif; ?>
      <?php if (!empty($company_name)): ?>
        <span>Company: "<?php echo safe_output($company_name); ?>"</span>
      <?php endif; ?>
      <?php if ($location !== 'all'): ?>
        <span>Location: <?php echo safe_output($location); ?></span>
      <?php endif; ?>
      <?php if ($duration !== 'all'): ?>
        <span>Duration: <?php echo safe_output($duration); ?></span>
      <?php endif; ?>
      <?php if ($industry !== 'all'): ?>
        <span>Industry: <?php echo safe_output($industry); ?></span>
      <?php endif; ?>
      <a href="browse_internship.php" style="color: #007bff; margin-left: 10px;">Clear All</a>
    </div>
    <?php endif; ?>
    <div class="filter-section">
      <form class="filter-form" method="GET">
        <div class="filter-group">
          <label for="job_title">🔍 Job Title:</label>
          <input type="text" id="job_title" name="job_title" placeholder="Search by title..." value="<?= safe_output($job_title) ?>" />
        </div>
        <div class="filter-group">
          <label for="company_name">🏢 Company Name:</label>
          <input type="text" id="company_name" name="company_name" placeholder="Search by company..." value="<?= safe_output($company_name) ?>" />
        </div>
        <div class="filter-group">
          <label for="location">📍 Filter by Location:</label>
          <select id="location" name="location">
            <option value="all">All Locations</option>
            <?php foreach ($locations_options as $loc): ?>
              <option value="<?= safe_output($loc) ?>" <?= $location === $loc ? 'selected' : '' ?>>
                <?= safe_output($loc) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group">
          <label for="duration">⏱️ Duration:</label>
          <select id="duration" name="duration">
            <option value="all">All Durations</option>
            <?php foreach ($durations_options as $dur): ?>
              <option value="<?= safe_output($dur) ?>" <?= $duration === $dur ? 'selected' : '' ?>>
                <?= safe_output($dur) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group">
          <label for="industry">🏭 Industry:</label>
          <select id="industry" name="industry">
            <option value="all">All Industries</option>
            <?php foreach ($industries_options as $ind): ?>
              <option value="<?= safe_output($ind) ?>" <?= $industry === $ind ? 'selected' : '' ?>>
                <?= safe_output($ind) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="search-btn">🔍 Search</button>
      </form>
    </div>
    <div class="table-container">
      <table class="internship-table">
        <thead>
          <tr>
            <th>📝 Title</th>
            <th>🏢 Company</th>
            <th>📍 Location</th>
            <th>⏱️ Duration</th>
            <th>📅 Start Date</th>
            <th>📅 End Date</th>
            <th>📋 Requirements</th>
            <th>⚡ Action</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $row_count = 0;
          if ($result) {
            while ($row = pg_fetch_assoc($result)): 
              $row_count++;
          ?>
            <tr>
              <td><?= safe_output($row['title']) ?></td>
              <td><?= safe_output($row['company_name']) ?></td>
              <td><?= safe_output($row['location']) ?></td>
              <td><?= safe_output($row['duration']) ?></td>
              <td><?= date("d/m/Y", strtotime($row['start_date'])) ?></td>
              <td><?= date("d/m/Y", strtotime($row['end_date'])) ?></td>
              <td><?= safe_output($row['requirements']) ?></td>
              <td>
                <?php if (get_current_user_type() === 'student'): ?>
                    <?php if (in_array($row['internship_id'], $applied_ids)): ?>
                        <span class="apply-btn" style="background: #aaa; cursor: not-allowed; opacity: 0.7; pointer-events: none;">Already Applied</span>
                    <?php else: ?>
                        <a href="../Applications/apply_form.php?internship_id=<?= safe_output($row['internship_id']) ?>" class="apply-btn">📝 Apply</a>
                    <?php endif; ?>
                <?php else: ?>
                    <span style="opacity: 0.6;">👁️ View Only</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php 
            endwhile;
          }
          if ($row_count === 0): ?>
            <tr>
              <td colspan="8" class="no-results">
                <?php if ($result === false): ?>
                  ⚠️ Unable to load internships at this time. Please try again later.
                <?php else: ?>
                  📭 No internships found matching your criteria. Try adjusting your filters.
                <?php endif; ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($row_count > 0): ?>
    <div style="text-align: center; margin-top: 20px; opacity: 0.8;">
      Found <?php echo $row_count; ?> internship<?php echo $row_count !== 1 ? 's' : ''; ?> matching your criteria
    </div>
    <?php endif; ?>
  </div>
  <footer class="footer">
    Intern Connect &copy; 2025 | All Rights Reserved
  </footer>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const selects = document.querySelectorAll('#location, #duration, #industry');
      selects.forEach(select => {
        select.addEventListener('change', function() {
          setTimeout(() => {
            this.form.submit();
          }, 300);
        });
      });
      const form = document.querySelector('.filter-form');
      const searchBtn = document.querySelector('.search-btn');
      form.addEventListener('submit', function() {
        searchBtn.innerHTML = '⏳ Searching...';
        searchBtn.disabled = true;
      });
    });
  </script>
</body>
</html>
<?php
$close_conn = @create_connection();
if ($close_conn) {
    pg_close($close_conn);
}
?>
