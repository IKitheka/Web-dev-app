<?php
require_once '../database/connection.php';
require_once '../database/auth_helpers.php';

// Check authentication - admins only
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../Authentication/login.php');
    exit();
}

$conn = create_connection();

// Handle search and filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$year_filter = $_GET['year'] ?? '';

// Build search query
$search_conditions = [];
$search_params = [];
$param_count = 0;

if (!empty($search)) {
    $search_term = '%' . $search . '%';
    $search_conditions[] = "(LOWER(s.name) LIKE LOWER($" . (++$param_count) . ") OR LOWER(e.company_name) LIKE LOWER($" . $param_count . ") OR LOWER(c.verification_code) LIKE LOWER($" . $param_count . "))";
    $search_params[] = $search_term;
}

if (!empty($year_filter)) {
    $search_conditions[] = "EXTRACT(YEAR FROM c.issue_date) = $" . (++$param_count);
    $search_params[] = $year_filter;
}

$where_clause = '';
if (!empty($search_conditions)) {
    $where_clause = ' WHERE ' . implode(' AND ', $search_conditions);
}

// Get certificate count
$count_sql = "
    SELECT COUNT(*) as total 
    FROM \"Certificates\" c
    JOIN \"Applications\" a ON c.application_id = a.application_id
    JOIN \"Students\" s ON a.student_id = s.student_id
    JOIN \"Internships\" i ON a.internship_id = i.internship_id
    JOIN \"Employers\" e ON i.employer_id = e.employer_id
    " . $where_clause;

$count_result = pg_query_params($conn, $count_sql, $search_params);
$total_certificates = 0;
if ($count_result) {
    $count_row = pg_fetch_assoc($count_result);
    $total_certificates = $count_row['total'];
}

// Get certificates with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$certificates_sql = "
    SELECT c.certificate_id, c.issue_date, c.certificate_url, c.verification_code, c.created_at,
           s.student_id, s.name as student_name, s.email as student_email,
           i.title as internship_title, i.start_date, i.end_date,
           e.company_name, e.industry,
           r.rating, r.completion_date,
           admin.full_name as issued_by
    FROM \"Certificates\" c
    JOIN \"Applications\" a ON c.application_id = a.application_id
    JOIN \"Students\" s ON a.student_id = s.student_id
    JOIN \"Internships\" i ON a.internship_id = i.internship_id
    JOIN \"Employers\" e ON i.employer_id = e.employer_id
    JOIN \"Results\" r ON a.application_id = r.application_id
    LEFT JOIN \"Administrators\" admin ON c.admin_id = admin.admin_id
    " . $where_clause . "
    ORDER BY c.issue_date DESC, c.created_at DESC
    LIMIT $" . (++$param_count) . " OFFSET $" . (++$param_count);

$search_params[] = $per_page;
$search_params[] = $offset;

$result = pg_query_params($conn, $certificates_sql, $search_params);

// Get available years for filter
$years_sql = "SELECT DISTINCT EXTRACT(YEAR FROM issue_date) as year FROM \"Certificates\" ORDER BY year DESC";
$years_result = pg_query($conn, $years_sql);
$available_years = [];
if ($years_result) {
    while ($year_row = pg_fetch_assoc($years_result)) {
        $available_years[] = $year_row['year'];
    }
}

// Calculate pagination
$total_pages = ceil($total_certificates / $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Management | Intern Connect</title>
    <link rel="icon" href="../static/images/title.png">
    <link rel="stylesheet" href="../static/css/index.css">
    <style>
        .certificates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .certificate-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 15px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .certificate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .certificate-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #fbbf24, #f59e0b, #d97706);
        }

        .cert-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .cert-badge {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .cert-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .detail-item.full-width {
            grid-column: 1 / -1;
        }

        .detail-label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
            font-weight: 500;
        }

        .detail-value {
            font-size: 0.9rem;
            color: white;
            font-weight: 500;
            word-break: break-word;
        }

        .verification-code {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem;
            border-radius: 6px;
            font-family: monospace;
            font-weight: 600;
            color: var(--color-cyan-300);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 0.85rem;
            text-align: center;
        }

        .cert-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
            justify-content: center;
            min-width: fit-content;
        }

        .download-btn {
            background: linear-gradient(135deg, var(--color-purple-900), var(--color-indigo-800));
            color: white;
        }

        .download-btn:hover {
            background: linear-gradient(135deg, var(--color-indigo-800), var(--color-purple-900));
        }

        .verify-btn {
            background: linear-gradient(135deg, var(--color-cyan-500), var(--color-cyan-400));
            color: white;
        }

        .verify-btn:hover {
            background: linear-gradient(135deg, var(--color-cyan-400), var(--color-cyan-300));
        }

        .view-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .view-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .search-section {
            background: rgba(255, 255, 255, 0.08);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .search-form {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: end;
        }

        .search-group {
            flex: 1;
            min-width: 200px;
        }

        .certificates-count {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: white;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .certificates-count::before {
            content: '🏆';
        }

        .no-certificates {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            background: rgba(255, 255, 255, 0.05);
            border: 2px dashed rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.1rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .page-btn {
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .page-btn:hover,
        .page-btn.active {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--color-cyan-300);
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
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
    
    <header class="header">CERTIFICATE MANAGEMENT</header>
    
    <?php require_once '../includes/navigation.php'; echo render_navigation('certificates'); ?>

    <div class="content">
        <h1 class="welcome">Certificate Management</h1>
        
        <!-- Statistics Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_certificates; ?></div>
                <div class="stat-label">Total Certificates</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $this_year_sql = "SELECT COUNT(*) as count FROM \"Certificates\" WHERE EXTRACT(YEAR FROM issue_date) = EXTRACT(YEAR FROM CURRENT_DATE)";
                    $this_year_result = pg_query($conn, $this_year_sql);
                    $this_year_count = 0;
                    if ($this_year_result) {
                        $this_year_row = pg_fetch_assoc($this_year_result);
                        $this_year_count = $this_year_row['count'];
                    }
                    echo $this_year_count;
                    ?>
                </div>
                <div class="stat-label">This Year</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $pending_sql = "
                        SELECT COUNT(*) as count 
                        FROM \"Results\" r
                        LEFT JOIN \"Certificates\" c ON r.application_id = c.application_id
                        WHERE c.certificate_id IS NULL
                    ";
                    $pending_result = pg_query($conn, $pending_sql);
                    $pending_count = 0;
                    if ($pending_result) {
                        $pending_row = pg_fetch_assoc($pending_result);
                        $pending_count = $pending_row['count'];
                    }
                    echo $pending_count;
                    ?>
                </div>
                <div class="stat-label">Pending Issuance</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $avg_rating_sql = "
                        SELECT ROUND(AVG(r.rating), 1) as avg_rating 
                        FROM \"Results\" r
                        JOIN \"Certificates\" c ON r.application_id = c.application_id
                    ";
                    $avg_rating_result = pg_query($conn, $avg_rating_sql);
                    $avg_rating = 0;
                    if ($avg_rating_result) {
                        $avg_rating_row = pg_fetch_assoc($avg_rating_result);
                        $avg_rating = $avg_rating_row['avg_rating'] ?? 0;
                    }
                    echo $avg_rating ? $avg_rating . '/5' : 'N/A';
                    ?>
                </div>
                <div class="stat-label">Avg Rating</div>
            </div>
        </div>
        
        <!-- Search Section -->
        <div class="search-section">
            <form class="search-form" method="GET">
                <div class="search-group">
                    <label class="search-label" for="search">Search Certificates</label>
                    <input type="text" id="search" name="search" class="search-input" 
                           placeholder="Student name, company, or verification code..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="search-group">
                    <label class="search-label" for="year">Issue Year</label>
                    <select id="year" name="year" class="search-select">
                        <option value="">All Years</option>
                        <?php foreach ($available_years as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo ($year_filter == $year) ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="search-btn">🔍 Search</button>
                <?php if (!empty($search) || !empty($year_filter)): ?>
                    <a href="certificate_list.php" class="search-btn" style="background: rgba(255,255,255,0.2); text-decoration: none;">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="certificates-count">
            Total Certificates: <?php echo $total_certificates; ?>
            <?php if (!empty($search) || !empty($year_filter)): ?>
                (filtered)
            <?php endif; ?>
        </div>

        <div class="certificates-grid">
            <?php
            if ($result && $total_certificates > 0) {
                while ($row = pg_fetch_assoc($result)) {
                    // Generate initials for student
                    $name_parts = explode(' ', trim($row['student_name']));
                    $initials = '';
                    foreach ($name_parts as $part) {
                        if (!empty($part)) {
                            $initials .= strtoupper(substr($part, 0, 1));
                            if (strlen($initials) >= 2) break;
                        }
                    }
                    if (strlen($initials) < 2 && !empty($name_parts)) {
                        $initials = strtoupper(substr($name_parts[0], 0, 2));
                    }
                    
                    echo '<div class="certificate-card">';
                    echo '  <div class="cert-header">';
                    echo '    <div>';
                    echo '      <h3 style="color: white; margin: 0 0 0.5rem 0; font-size: 1.1rem;">' . htmlspecialchars($row['student_name']) . '</h3>';
                    echo '      <p style="color: rgba(255,255,255,0.8); margin: 0; font-size: 0.9rem;">' . htmlspecialchars($row['internship_title']) . '</p>';
                    echo '    </div>';
                    echo '    <div class="cert-badge">🏆 Certified</div>';
                    echo '  </div>';
                    
                    echo '  <div class="cert-details">';
                    echo '    <div class="detail-item">';
                    echo '      <div class="detail-label">Company</div>';
                    echo '      <div class="detail-value">' . htmlspecialchars($row['company_name']) . '</div>';
                    echo '    </div>';
                    echo '    <div class="detail-item">';
                    echo '      <div class="detail-label">Issue Date</div>';
                    echo '      <div class="detail-value">' . date('M j, Y', strtotime($row['issue_date'])) . '</div>';
                    echo '    </div>';
                    echo '    <div class="detail-item">';
                    echo '      <div class="detail-label">Duration</div>';
                    echo '      <div class="detail-value">' . date('M Y', strtotime($row['start_date'])) . ' - ' . date('M Y', strtotime($row['end_date'])) . '</div>';
                    echo '    </div>';
                    echo '    <div class="detail-item">';
                    echo '      <div class="detail-label">Rating</div>';
                    echo '      <div class="detail-value">';
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= $row['rating'] ? '<span style="color: #fbbf24;">★</span>' : '<span style="color: rgba(255,255,255,0.3);">★</span>';
                    }
                    echo ' ' . $row['rating'] . '/5</div>';
                    echo '    </div>';
                    echo '    <div class="detail-item full-width">';
                    echo '      <div class="detail-label">Verification Code</div>';
                    echo '      <div class="verification-code">' . htmlspecialchars($row['verification_code']) . '</div>';
                    echo '    </div>';
                    echo '  </div>';
                    
                    echo '  <div class="cert-actions">';
                    echo '    <a href="download_certificate.php?certificate_id=' . htmlspecialchars($row['certificate_id']) . '" class="action-btn download-btn" target="_blank">📄 Download</a>';
                    echo '    <a href="verify_certificate.php?code=' . htmlspecialchars($row['verification_code']) . '" class="action-btn verify-btn">🔍 Verify</a>';
                    echo '    <a href="../Results/view_results.php?application_id=' . htmlspecialchars($row['certificate_id']) . '" class="action-btn view-btn">👁️ Details</a>';
                    echo '  </div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="no-certificates">';
                echo '  <h3>🏆 No Certificates Found</h3>';
                if (!empty($search) || !empty($year_filter)) {
                    echo '  <p>Try adjusting your search criteria or clearing the filters.</p>';
                } else {
                    echo '  <p>No certificates have been issued yet.</p>';
                    echo '  <p><a href="../Results/complete_internship.php" style="color: var(--color-cyan-300);">Check for completed internships ready for certification →</a></p>';
                }
                echo '</div>';
            }
            ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&year=<?php echo urlencode($year_filter); ?>" class="page-btn">« Previous</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&year=<?php echo urlencode($year_filter); ?>" 
                       class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&year=<?php echo urlencode($year_filter); ?>" class="page-btn">Next »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">Intern Connect &copy; 2025 | All Rights Reserved</footer>
</body>
</html>
<?php pg_close($conn); ?>
