<?php
session_start();
include '../database/connection.php';
$conn = create_connection();

// Get filter parameters
$job_title = $_GET['job_title'] ?? '';
$company_name = $_GET['company_name'] ?? '';
$location = $_GET['location'] ?? 'all';
$duration = $_GET['duration'] ?? 'all';
$industry = $_GET['industry'] ?? 'all';

// Build the query with filters
$query = "
  SELECT i.title, e.company_name, i.location, i.duration, 
         i.start_date, i.end_date, i.requirements, i.internship_id
  FROM Internships i
  JOIN Employers e ON i.employer_id = e.employer_id
  WHERE i.is_active = TRUE
";

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

// Execute query
if (!empty($params)) {
    $result = pg_query_params($conn, $query, $params);
} else {
    $result = pg_query($conn, $query);
}

// Get unique values for filter dropdowns
$locations_result = pg_query($conn, "SELECT DISTINCT location FROM Internships WHERE is_active = TRUE AND location IS NOT NULL ORDER BY location");
$durations_result = pg_query($conn, "SELECT DISTINCT duration FROM Internships WHERE is_active = TRUE AND duration IS NOT NULL ORDER BY duration");
$industries_result = pg_query($conn, "SELECT DISTINCT industry FROM Employers ORDER BY industry");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Intern Connect | Browse Internships</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      overflow-x: auto;
    }

    .container {
      min-height: 100vh;
      padding: 20px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .header {
      text-align: center;
      font-size: 2.5rem;
      font-weight: 300;
      margin-bottom: 40px;
      text-shadow: 0 2px 4px rgba(0,0,0,0.1);
      letter-spacing: 2px;
    }

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
    }

    .internship-table td {
      padding: 18px 15px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      font-size: 0.9rem;
      line-height: 1.4;
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

    .internship-table tbody tr:nth-child(1) {
      border-color: rgba(255, 99, 132, 0.8);
      background: rgba(255, 99, 132, 0.1);
    }

    .internship-table tbody tr:nth-child(2) {
      border-color: rgba(255, 99, 132, 0.8);
      background: rgba(255, 99, 132, 0.1);
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

    .footer {
      text-align: center;
      margin-top: 40px;
      padding: 20px;
      opacity: 0.8;
      font-size: 0.9rem;
    }

    .no-results {
      text-align: center;
      padding: 40px;
      font-size: 1.1rem;
      opacity: 0.8;
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
      
      .header {
        font-size: 2rem;
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
  <div class="container">
    <header class="header">
      Available Internship Opportunities
    </header>

    <div class="filter-section">
      <form class="filter-form" method="GET">
        <div class="filter-group">
          <label for="job_title">Job Title:</label>
          <input type="text" id="job_title" name="job_title" placeholder="Search by title..." value="<?= htmlspecialchars($job_title) ?>" />
        </div>

        <div class="filter-group">
          <label for="company_name">Company Name:</label>
          <input type="text" id="company_name" name="company_name" placeholder="Search by company..." value="<?= htmlspecialchars($company_name) ?>" />
        </div>

        <div class="filter-group">
          <label for="location">Filter by Location:</label>
          <select id="location" name="location">
            <option value="all">All</option>
            <?php while ($row = pg_fetch_assoc($locations_result)): ?>
              <option value="<?= htmlspecialchars($row['location']) ?>" <?= $location === $row['location'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['location']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="filter-group">
          <label for="duration">Duration:</label>
          <select id="duration" name="duration">
            <option value="all">All</option>
            <?php while ($row = pg_fetch_assoc($durations_result)): ?>
              <option value="<?= htmlspecialchars($row['duration']) ?>" <?= $duration === $row['duration'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['duration']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="filter-group">
          <label for="industry">Industry:</label>
          <select id="industry" name="industry">
            <option value="all">All</option>
            <?php while ($row = pg_fetch_assoc($industries_result)): ?>
              <option value="<?= htmlspecialchars($row['industry']) ?>" <?= $industry === $row['industry'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['industry']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <button type="submit" class="search-btn">Search</button>
      </form>
    </div>

    <div class="table-container">
      <table class="internship-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Company</th>
            <th>Location</th>
            <th>Duration</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Requirements</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $row_count = 0;
          while ($row = pg_fetch_assoc($result)): 
            $row_count++;
          ?>
            <tr>
              <td><?= htmlspecialchars($row['title']) ?></td>
              <td><?= htmlspecialchars($row['company_name']) ?></td>
              <td><?= htmlspecialchars($row['location']) ?></td>
              <td><?= htmlspecialchars($row['duration']) ?></td>
              <td><?= date("d/m/Y", strtotime($row['start_date'])) ?></td>
              <td><?= date("d/m/Y", strtotime($row['end_date'])) ?></td>
              <td><?= htmlspecialchars($row['requirements']) ?></td>
              <td>
                <a href="apply_form.html?internship_id=<?= $row['internship_id'] ?>" class="apply-btn">Apply</a>
              </td>
            </tr>
          <?php endwhile; ?>
          
          <?php if ($row_count === 0): ?>
            <tr>
              <td colspan="8" class="no-results">
                No internships found matching your criteria.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <footer class="footer">
      Intern Connect © 2025 | All Rights Reserved
    </footer>
  </div>
</body>
</html> 