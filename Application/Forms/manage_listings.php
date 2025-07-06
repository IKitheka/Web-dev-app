<?php
require_once 'connection.php';
session_start();

// Track user activity with cookies
setcookie("last_visited", "post_internship_page", time() + (86400 * 30), "/");
setcookie("user_activity", "posting_internship", time() + (86400 * 30), "/");

// Check if user has posted before
if (!isset($_COOKIE['user_type'])) {
    setcookie("user_type", "employer", time() + (86400 * 30), "/");
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Track form submission attempt
    setcookie("last_form_attempt", date('Y-m-d H:i:s'), time() + (86400 * 7), "/");
    
    if (empty($title) || empty($duration) || empty($location) || empty($start_date) || empty($end_date) || empty($requirements) || empty($description)) {
        $error = 'All fields are required.';
        // Track validation error
        setcookie("last_error", "validation_failed", time() + (86400 * 7), "/");
    } elseif (strtotime($start_date) >= strtotime($end_date)) {
        $error = 'End date must be after start date.';
        // Track date validation error
        setcookie("last_error", "date_validation_failed", time() + (86400 * 7), "/");
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO internships (title, duration, location, start_date, end_date, requirements, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            
            if ($stmt->execute([$title, $duration, $location, $start_date, $end_date, $requirements, $description])) {
                $message = 'Internship posted successfully!';
                
                // Track successful posting
                setcookie("last_success", "internship_posted", time() + (86400 * 30), "/");
                setcookie("posts_count", (isset($_COOKIE['posts_count']) ? $_COOKIE['posts_count'] + 1 : 1), time() + (86400 * 30), "/");
                setcookie("last_post_title", $title, time() + (86400 * 30), "/");
                
                // Clear form data
                $title = $duration = $location = $start_date = $end_date = $requirements = $description = '';
            } else {
                $error = 'Error posting internship. Please try again.';
                setcookie("last_error", "database_insert_failed", time() + (86400 * 7), "/");
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
            setcookie("last_error", "database_connection_failed", time() + (86400 * 7), "/");
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    // Clear cookies
    setcookie("last_visited", "", time() - 3600, "/");
    setcookie("user_activity", "", time() - 3600, "/");
    setcookie("user_type", "", time() - 3600, "/");
    setcookie("last_success", "", time() - 3600, "/");
    setcookie("posts_count", "", time() - 3600, "/");
    setcookie("last_post_title", "", time() - 3600, "/");
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
	<title>Intern Connect | Post Internship</title>
	<link rel="icon" href="/static/images/title.png">
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
	
	<header class="header">POST INTERNSHIP</header>
	<nav class="nav">
		<a href="#">INTERN CONNECT</a>
		<a href="#">Dashboard</a>
		<a class="active" href="#">Post a Job</a>
		<a href="#">Manage Listings</a>
		<a href="#">Applicants</a>
		<a href="#">Profile</a>
		<a href="?logout=1" style="margin-left: auto;">Log out</a>
	</nav>
	
	<div class="content">
		<h1 class="welcome">Post New Internship</h1>
		
		<?php if (isset($_COOKIE['last_success']) && $_COOKIE['last_success'] === 'internship_posted'): ?>
		<div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
			Welcome back! You've successfully posted 
			<?php echo isset($_COOKIE['posts_count']) ? $_COOKIE['posts_count'] : '1'; ?> internship(s).
			<?php if (isset($_COOKIE['last_post_title'])): ?>
				Last posted: "<?php echo htmlspecialchars($_COOKIE['last_post_title']); ?>"
			<?php endif; ?>
		</div>
		<?php endif; ?>
		
		<?php if ($message): ?>
		<div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
			<?php echo htmlspecialchars($message); ?>
		</div>
		<?php endif; ?>
		
		<?php if ($error): ?>
		<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
			<?php echo htmlspecialchars($error); ?>
		</div>
		<?php endif; ?>
		
		<form method="POST" style="max-width: 600px; margin: 0 auto;">
			<div style="margin-bottom: 20px;">
				<label style="display: block; margin-bottom: 5px; font-weight: bold;">Title:</label>
				<input type="text" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" 
					   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required>
			</div>
			
			<div style="margin-bottom: 20px;">
				<label style="display: block; margin-bottom: 5px; font-weight: bold;">Duration:</label>
				<input type="text" name="duration" value="<?php echo htmlspecialchars($duration ?? ''); ?>" 
					   placeholder="e.g., 3 months, 6 months"
					   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required>
			</div>
			
			<div style="margin-bottom: 20px;">
				<label style="display: block; margin-bottom: 5px; font-weight: bold;">Location:</label>
				<input type="text" name="location" value="<?php echo htmlspecialchars($location ?? ''); ?>" 
					   placeholder="e.g., Nairobi, Remote"
					   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required>
			</div>
			
			<div style="display: flex; gap: 20px; margin-bottom: 20px;">
				<div style="flex: 1;">
					<label style="display: block; margin-bottom: 5px; font-weight: bold;">Start Date:</label>
					<input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date ?? ''); ?>" 
						   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required>
				</div>
				<div style="flex: 1;">
					<label style="display: block; margin-bottom: 5px; font-weight: bold;">End Date:</label>
					<input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date ?? ''); ?>" 
						   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required>
				</div>
			</div>
			
			<div style="margin-bottom: 20px;">
				<label style="display: block; margin-bottom: 5px; font-weight: bold;">Requirements:</label>
				<textarea name="requirements" rows="4" 
						  style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; resize: vertical;" 
						  placeholder="List the requirements for this internship..." required><?php echo htmlspecialchars($requirements ?? ''); ?></textarea>
			</div>
			
			<div style="margin-bottom: 20px;">
				<label style="display: block; margin-bottom: 5px; font-weight: bold;">Description:</label>
				<textarea name="description" rows="6" 
						  style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; resize: vertical;" 
						  placeholder="Describe the internship role and responsibilities..." required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
			</div>
			
			<button type="submit" class="btn" style="background: #4f46e5; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
				Post Internship
			</button>
		</form>
	</div>
	
	<footer class="footer">Intern Connect &copy; 2025 | All Rights Reserved</footer>
</body>
</html>
