<?php
require_once 'connection.php';
session_start();

// Basic tracking cookies
setcookie("last_visited", "employer_list_page", time() + (86400 * 30), "/");
setcookie("user_activity", "viewing_employers", time() + (86400 * 30), "/");

$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $employer_id = $_POST['employer_id'];
    
    try {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE employers SET status = 'approved' WHERE id = ?");
            if ($stmt->execute([$employer_id])) {
                $message = 'Employer approved successfully!';
                setcookie("last_success", "employer_approved", time() + (86400 * 30), "/");
            }
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE employers SET status = 'rejected' WHERE id = ?");
            if ($stmt->execute([$employer_id])) {
                $message = 'Employer rejected successfully!';
                setcookie("last_success", "employer_rejected", time() + (86400 * 30), "/");
            }
        }
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
        setcookie("last_error", "database_error", time() + (86400 * 7), "/");
    }
}

// Fetch employers
try {
    $stmt = $pdo->prepare("SELECT * FROM employers ORDER BY created_at DESC");
    $stmt->execute();
    $employers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching employers: ' . $e->getMessage();
    $employers = [];
}

// Handle logout
if (isset($_GET['logout'])) {
    setcookie("last_visited", "", time() - 3600, "/");
    setcookie("user_activity", "", time() - 3600, "/");
    setcookie("last_success", "", time() - 3600, "/");
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
        
        <?php if ($message): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
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
                    <?php if (!empty($employers)): ?>
                        <?php foreach ($employers as $employer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($employer['company_name']); ?></td>
                                <td><?php echo htmlspecialchars($employer['email']); ?></td>
                                <td><?php echo htmlspecialchars($employer['industry']); ?></td>
                                <td><?php echo htmlspecialchars($employer['phone']); ?></td>
                                <td>
                                    <span class="status-<?php echo strtolower($employer['status']); ?>">
                                        <?php echo ucfirst($employer['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($employer['created_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="employer_id" value="<?php echo $employer['id']; ?>">
                                        <button type="submit" name="action" value="view" class="btn">View</button>
                                        <?php if ($employer['status'] === 'pending'): ?>
                                            <button type="submit" name="action" value="approve" class="btn" style="background: #28a745;">Approve</button>
                                            <button type="submit" name="action" value="reject" class="btn" style="background: #dc3545;">Reject</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px;">No employers found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <footer class="footer">Intern Connect &copy; 2025 | All Rights Reserved</footer>
</body>
</html>
