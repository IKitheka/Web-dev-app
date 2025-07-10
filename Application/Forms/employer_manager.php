<?php
require_once '../database/connection.php';
require_once '../database/auth_helpers.php';
require_auth('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
  header('Content-Type: application/json');
  $action = $_GET['action'];
  $input = json_decode(file_get_contents('php://input'), true) ?: [];
  $conn = create_connection();
  try {
    switch ($action) {
      case 'get_details':
        if (empty($input['employer_id'])) throw new Exception('ID required');
        $res = pg_query_params($conn,
          'SELECT employer_id, company_name, email, phone, industry, location, website, company_size, status, deleted_at, TO_CHAR(created_at, \'DD/MM/YYYY\') AS date_joined, about_company FROM "Employers" WHERE employer_id = $1',
          [$input['employer_id']]
        );
        if (!$res || pg_num_rows($res) === 0) throw new Exception('Not found');
        echo json_encode(['success' => true, 'employer' => pg_fetch_assoc($res)]);
        break;

      case 'toggle_status':
        $id = $input['employer_id'] ?? '';
        $new = $input['new_status'] ?? '';
        $reason = trim($input['reason'] ?? '');
        if (!$id || !in_array($new, ['active','disabled','suspended'])) throw new Exception('Invalid');
        if (in_array($new, ['disabled', 'suspended']) && !$reason) throw new Exception('Reason required');
        pg_query($conn, 'BEGIN');
        $chk = pg_query_params($conn, 'SELECT status FROM "Employers" WHERE employer_id=$1 AND deleted_at IS NULL', [$id]);
        if (!$chk || pg_num_rows($chk) === 0) {
          pg_query($conn, 'ROLLBACK');
          throw new Exception('Not found');
        }
        $curr = pg_fetch_assoc($chk)['status'] ?: 'active';
        if ($curr === $new) {
          pg_query($conn, 'ROLLBACK');
          throw new Exception('Already ' . $new);
        }
        $admin = $_SESSION['admin_id'] ?? null;
        if ($new === 'active') {
          $sql = 'UPDATE "Employers" SET status=$1, disabled_at=NULL, disabled_by=NULL, disabled_reason=NULL, updated_at=NOW() WHERE employer_id=$2';
          $params = [$new, $id];
        } else {
          $sql = 'UPDATE "Employers" SET status=$1, disabled_at=NOW(), disabled_by=$2, disabled_reason=$3, updated_at=NOW() WHERE employer_id=$4';
          $params = [$new, $admin, $reason, $id];
        }
        if (!pg_query_params($conn, $sql, $params)) {
          pg_query($conn, 'ROLLBACK');
          throw new Exception('Update failed');
        }
        pg_query($conn, 'COMMIT');
        echo json_encode(['success' => true]);
        break;

      case 'delete':
        $id = $input['employer_id'] ?? '';
        $reason = trim($input['reason'] ?? '');
        if (!$id || !$reason) throw new Exception('ID and reason');
        pg_query($conn, 'BEGIN');
        $chk = pg_query_params($conn, 'SELECT 1 FROM "Employers" WHERE employer_id=$1 AND deleted_at IS NULL', [$id]);
        if (!$chk || pg_num_rows($chk) === 0) {
          pg_query($conn, 'ROLLBACK');
          throw new Exception('Not found');
        }
        $admin = $_SESSION['admin_id'] ?? null;
        if (!pg_query_params($conn, 'UPDATE "Employers" SET deleted_at=NOW(), deleted_by=$1, updated_at=NOW() WHERE employer_id=$2', [$admin, $id])) {
          pg_query($conn, 'ROLLBACK');
          throw new Exception('Delete failed');
        }
        pg_query($conn, 'COMMIT');
        echo json_encode(['success' => true]);
        break;

      case 'restore':
        $id = $input['employer_id'] ?? '';
        if (!$id) throw new Exception('ID required');
        pg_query($conn, 'BEGIN');
        $chk = pg_query_params($conn, 'SELECT 1 FROM "Employers" WHERE employer_id=$1 AND deleted_at IS NOT NULL', [$id]);
        if (!$chk || pg_num_rows($chk) === 0) {
          pg_query($conn, 'ROLLBACK');
          throw new Exception('Not found');
        }
        if (!pg_query_params($conn, "UPDATE \"Employers\" SET deleted_at=NULL, deleted_by=NULL, status='active', disabled_at=NULL, disabled_by=NULL, disabled_reason=NULL, updated_at=NOW() WHERE employer_id=$1", [$id])) {
          pg_query($conn, 'ROLLBACK');
          throw new Exception('Restore failed');
        }
        pg_query($conn, 'COMMIT');
        echo json_encode(['success' => true]);
        break;

      default:
        throw new Exception('Unknown');
    }
  } catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  }
  pg_close($conn);
  exit;
}

$conn = create_connection();
$res = pg_query($conn, 'SELECT employer_id, company_name, email, phone, industry, location, website, company_size, status, deleted_at, TO_CHAR(created_at, \'DD/MM/YYYY\') AS date_joined, about_company FROM "Employers" ORDER BY company_name ASC');
$employers = [];
if ($res) {
  while ($row = pg_fetch_assoc($res)) {
    $employers[] = $row;
  }
}
pg_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employer Manager</title>
  <link rel="stylesheet" href="../static/css/index.css">
</head>
<body>
  <svg class="bg-animation" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:-1;pointer-events:none;opacity:0.3;" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
    <path fill="url(#aurora-gradient)" d="M0,128L60,138.7C120,149,240,171,360,154.7C480,139,600,85,720,96C840,107,960,181,1080,197.3C1200,213,1320,171,1380,149.3L1440,128L1440,0L1380,0C1320,0,1200,0,1080,0C960,0,840,0,720,0C600,0,480,0,360,0C240,0,120,0,60,0L0,0Z"></path>
    <defs>
      <linearGradient id="aurora-gradient" x1="0" y1="0" x2="1" y2="1">
        <stop offset="0%" stop-color="#4f46e5"/>
        <stop offset="50%" stop-color="#06b6d4"/>
        <stop offset="100%" stop-color="#8b5cf6"/>
      </linearGradient>
    </defs>
  </svg>

  <header class="header">EMPLOYER MANAGER</header>
  <?php require_once '../includes/navigation.php'; echo render_navigation('employers'); ?>

  <div class="content">
    <h1 class="welcome">Manage Employers</h1>
    <table class="apps-table">
      <thead>
        <tr><th>Company</th><th>Email</th><th>Phone</th><th>Industry</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($employers as $e): ?>
          <tr>
            <td><?= htmlspecialchars($e['company_name']) ?></td>
            <td><?= htmlspecialchars($e['email']) ?></td>
            <td><?= htmlspecialchars($e['phone']) ?></td>
            <td><?= htmlspecialchars($e['industry']) ?></td>
            <td><?= htmlspecialchars($e['status']) ?><?= $e['deleted_at'] ? ' (deleted)' : '' ?></td>
            <td>
              <button class="btn" onclick="viewEmployer('<?= $e['employer_id'] ?>')">View</button>
              <?php if ($e['status'] === 'active' && !$e['deleted_at']): ?>
                <button class="btn" onclick="toggleStatus('<?= $e['employer_id'] ?>','disabled')">Disable</button>
              <?php elseif (!$e['deleted_at']): ?>
                <button class="btn" onclick="toggleStatus('<?= $e['employer_id'] ?>','active')">Enable</button>
              <?php endif; ?>
              <?php if (!$e['deleted_at']): ?>
                <button class="btn" onclick="deleteEmployer('<?= $e['employer_id'] ?>')">Delete</button>
              <?php else: ?>
                <button class="btn" onclick="restoreEmployer('<?= $e['employer_id'] ?>')">Restore</button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <footer class="footer">Intern Connect &copy; 2025 | All Rights Reserved</footer>

  <script>
    function api(action, data) {
      return fetch('?action=' + action, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      }).then(res => res.json());
    }

    function toggleStatus(id, status) {
      const reason = status === 'active' ? '' : prompt('Reason for status change:');
      if (status !== 'active' && !reason) return;
      api('toggle_status', { employer_id: id, new_status: status, reason })
        .then(r => r.success ? location.reload() : alert(r.message));
    }

    function deleteEmployer(id) {
      const reason = prompt('Reason for deletion:');
      if (!reason) return;
      api('delete', { employer_id: id, reason })
        .then(r => r.success ? location.reload() : alert(r.message));
    }

    function restoreEmployer(id) {
      api('restore', { employer_id: id })
        .then(r => r.success ? location.reload() : alert(r.message));
    }

    function viewEmployer(id) {
      api('get_details', { employer_id: id })
        .then(r => {
          if (!r.success) return alert(r.message);
          const e = r.employer;
          alert(`Company: ${e.company_name}\nEmail: ${e.email}\nPhone: ${e.phone || 'N/A'}\nStatus: ${e.status}`);
        });
    }
  </script>
</body>
</html>
