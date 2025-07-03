<?php
require_once '../database/connection.php';
$conn = create_connection();
$location = $_GET['location'] ?? 'all';
$duration = $_GET['duration'] ?? 'all';
$industry = $_GET['industry'] ?? 'all';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Intern Connect | Browse Internships</title>
  <link rel="icon" type="image/x-icon" href="/static/images/title.png">
  <link rel="stylesheet" href="../static/css/index.css">
</head>
<body>
  <!-- Aurora Background Animation -->
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
  <header class="header">BROWSE INTERNSHIPS</header>
  <nav class="nav">
    <a href="#">INTERN CONNECT</a>
    <a href="#">Dashboard</a>
    <a href="#">Browse Internships</a>
    <a href="#">My Applications</a>
    <a href="#">Profile</a>
    <a href="#" style="margin-left: auto;">Log out</a>
  </nav>
  <div class="content">
    <h1 class="welcome">Available Internship Opportunities</h1>
    <div class="filter-form">
      <form class="form" method="GET">
        <label for="location">Filter by Location:</label>
        <select id="location" name="location">
          <option value="all" <?php if($location=='all') echo 'selected'; ?>>All</option>
          <option value="Remote" <?php if($location=='Remote') echo 'selected'; ?>>Remote</option>
          <option value="Onsite - Farming Town" <?php if($location=='Onsite - Farming Town') echo 'selected'; ?>>Onsite - Farming Town</option>
        </select>
        <label for="duration">Duration:</label>
        <select id="duration" name="duration">
          <option value="all" <?php if($duration=='all') echo 'selected'; ?>>All</option>
          <option value="3 months" <?php if($duration=='3 months') echo 'selected'; ?>>3 months</option>
          <option value="6 months" <?php if($duration=='6 months') echo 'selected'; ?>>6 months</option>
        </select>
        <label for="industry">Industry:</label>
        <select id="industry" name="industry">
          <option value="all" <?php if($industry=='all') echo 'selected'; ?>>All</option>
          <option value="Technology" <?php if($industry=='Technology') echo 'selected'; ?>>Technology</option>
          <option value="Agriculture" <?php if($industry=='Agriculture') echo 'selected'; ?>>Agriculture</option>
        </select>
        <button type="submit" class="btn">Search</button>
      </form>
    </div>
    <div class="internship-list">
      <table class="apps-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Company</th>
            <th>Location</th>
            <th>Duration</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Requirements</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $sql = "SELECT i.title, e.company_name, i.location, i.duration, TO_CHAR(i.start_date, 'DD/MM/YYYY') AS start_date, TO_CHAR(i.end_date, 'DD/MM/YYYY') AS end_date, i.requirements FROM Internships i JOIN Employers e ON i.employer_id = e.employer_id WHERE 1=1";
        $params = [];
        $index = 1;
        if ($location !== 'all') { $sql .= " AND i.location = $" . $index; $params[] = $location; $index++; }
        if ($duration !== 'all') { $sql .= " AND i.duration = $" . $index; $params[] = $duration; $index++; }
        if ($industry !== 'all') { $sql .= " AND e.industry = $" . $index; $params[] = $industry; $index++; }
        $sql .= " ORDER BY i.title ASC, e.company_name ASC";
        $result = pg_query_params($conn, $sql, $params);
        if ($result) {
          $row_count = 0;
          while ($row = pg_fetch_assoc($result)) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['title']) . '</td>';
            echo '<td>' . htmlspecialchars($row['company_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['location']) . '</td>';
            echo '<td>' . htmlspecialchars($row['duration']) . '</td>';
            echo '<td>' . htmlspecialchars($row['start_date']) . '</td>';
            echo '<td>' . htmlspecialchars($row['end_date']) . '</td>';
            echo '<td>' . htmlspecialchars($row['requirements']) . '</td>';
            echo '</tr>';
            $row_count++;
          }
          if ($row_count === 0) {
            echo '<tr><td colspan="7" style="text-align: center; font-style: italic;">No internships found matching your criteria.</td></tr>';
          }
        } else {
          echo '<tr><td colspan="7" style="color:red;">Error fetching internships.</td></tr>';
        }
        pg_close($conn);
        ?>
        </tbody>
      </table>
    </div>
  </div>
  <footer class="footer">Intern Connect &copy; 2025 | All Rights Reserved</footer>
</body>
</html> 