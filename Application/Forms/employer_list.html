<?php
// Start session and handle cookies
session_start();

// Set a cookie for the current page visit
setcookie("last_visited", "employers_page", time() + (86400 * 30), "/"); // 30 days

// Check if user preferences cookie exists, if not set default
if (!isset($_COOKIE['user_preferences'])) {
    setcookie("user_preferences", "default_view", time() + (86400 * 30), "/");
}

// Handle logout
if (isset($_GET['logout'])) {
    // Clear cookies
    setcookie("last_visited", "", time() - 3600, "/");
    setcookie("user_preferences", "", time() - 3600, "/");
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>Intern Connect | Employers</title>
	<link rel="icon" href="/static/images/title.png">
	<link rel="stylesheet" href="../static/css/index.css">
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
		<?php if (isset($_COOKIE['last_visited'])): ?>
		<p style="font-size: 14px; color: #666; margin-bottom: 20px;">
			Welcome back! Last visited: <?php echo htmlspecialchars($_COOKIE['last_visited']); ?>
		</p>
		<?php endif; ?>
		<div class="table-section">
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
					<tr>
						<td>TechNova Inc.</td>
						<td>contact@technova.com</td>
						<td>Technology</td>
						<td>555-123-4567</td>
						<td><span class="status-approved">Approved</span></td>
						<td>20/05/2025</td>
						<td><button class="btn">View</button></td>
					</tr>
					<tr>
						<td>GreenFields Ltd.</td>
						<td>hr@greenfields.com</td>
						<td>Agriculture</td>
						<td>555-234-5678</td>
						<td><span class="status-pending">Pending</span></td>
						<td>18/05/2025</td>
						<td><button class="btn">Review</button></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	<footer class="footer">Intern Connect &copy; 2025 | All Rights Reserved</footer>
</body>
</html>
