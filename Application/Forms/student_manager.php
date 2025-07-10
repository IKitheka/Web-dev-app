<?php
require_once '../database/connection.php';
require_once '../database/auth_helpers.php';
require_auth('admin');

// Handles all backend API requests for the page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $conn = create_connection();
    try {
        pg_query($conn, "BEGIN");

        switch ($action) {
            case 'get_details':
                $studentId = $input['student_id'] ?? null;
                if (!$studentId) throw new Exception('Student ID is required');
                $sql = "SELECT student_id, name, email, phone, department, academic_year, status, deleted_at, disabled_at, disabled_reason, TO_CHAR(created_at, 'YYYY-MM-DD HH24:MI:SS') as created_at, TO_CHAR(updated_at, 'YYYY-MM-DD HH24:MI:SS') as updated_at FROM \"Students\" WHERE student_id = $1";
                $res = pg_query_params($conn, $sql, [$studentId]);
                if (!$res || pg_num_rows($res) === 0) throw new Exception('Student not found');
                $student = pg_fetch_assoc($res);
                echo json_encode(['success' => true, 'student' => $student]);
                break;

            case 'update_profile':
                $required = ['student_id', 'name', 'email'];
                foreach ($required as $f) {
                    if (empty(trim($input[$f] ?? ''))) throw new Exception(ucfirst($f) . " is required");
                }
                $studentId = trim($input['student_id']);
                $name = trim($input['name']);
                $email = trim($input['email']);
                $phone = trim($input['phone'] ?? '');
                $dept = trim($input['department'] ?? '');
                $year = trim($input['academic_year'] ?? '');

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Invalid email format');
                if (strlen($name) < 2) throw new Exception('Name must be at least 2 characters');

                $chk_email = pg_query_params($conn, "SELECT 1 FROM \"Students\" WHERE email=$1 AND student_id!=$2", [$email, $studentId]);
                if (pg_num_rows($chk_email) > 0) throw new Exception('Email already in use by another student');

                $sql = "UPDATE \"Students\" SET name=$1, email=$2, phone=$3, department=$4, academic_year=$5, updated_at=CURRENT_TIMESTAMP WHERE student_id=$6";
                $params = [$name, $email, $phone, $dept, $year, $studentId];
                if (!pg_query_params($conn, $sql, $params)) throw new Exception('Failed to update student profile');
                echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
                break;

            case 'toggle_status':
                $id = $input['student_id'] ?? null;
                $new_status = $input['new_status'] ?? null;
                $reason = trim($input['reason'] ?? '');
                $allowed = ['active', 'disabled', 'suspended'];
                if (!$id || !in_array($new_status, $allowed)) throw new Exception('Invalid request');
                if (in_array($new_status, ['disabled', 'suspended']) && !$reason) throw new Exception('A reason is required to disable or suspend a student');

                $chk = pg_query_params($conn, "SELECT status FROM \"Students\" WHERE student_id=$1 AND deleted_at IS NULL", [$id]);
                if (pg_num_rows($chk) === 0) throw new Exception('Student not found or has been deleted');

                $current_status = pg_fetch_assoc($chk)['status'];
                if ($current_status === $new_status) throw new Exception("Student is already $new_status");

                $admin_id = $_SESSION['admin_id'] ?? null;
                if ($new_status === 'active') {
                    $sql = "UPDATE \"Students\" SET status=$1, disabled_at=NULL, disabled_by=NULL, disabled_reason=NULL, updated_at=CURRENT_TIMESTAMP WHERE student_id=$2";
                    $params = [$new_status, $id];
                } else {
                    $sql = "UPDATE \"Students\" SET status=$1, disabled_at=CURRENT_TIMESTAMP, disabled_by=$2, disabled_reason=$3, updated_at=CURRENT_TIMESTAMP WHERE student_id=$4";
                    $params = [$new_status, $admin_id, $reason, $id];
                }
                if (!pg_query_params($conn, $sql, $params)) throw new Exception('Failed to update student status');
                echo json_encode(['success' => true, 'message' => 'Student status updated']);
                break;

            case 'delete':
                $id = $input['student_id'] ?? null;
                $reason = trim($input['reason'] ?? '');
                if (!$id || !$reason) throw new Exception('Student ID and a reason for deletion are required');

                $chk = pg_query_params($conn, "SELECT 1 FROM \"Students\" WHERE student_id=$1 AND deleted_at IS NULL", [$id]);
                if (pg_num_rows($chk) === 0) throw new Exception('Student not found or already deleted');

                $admin_id = $_SESSION['admin_id'] ?? null;
                $sql = "UPDATE \"Students\" SET deleted_at=CURRENT_TIMESTAMP, deleted_by=$1, updated_at=CURRENT_TIMESTAMP WHERE student_id=$2";
                if (!pg_query_params($conn, $sql, [$admin_id, $id])) throw new Exception('Failed to delete student');
                echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
                break;

            case 'restore':
                $id = $input['student_id'] ?? null;
                if (!$id) throw new Exception('Student ID is required');

                $chk = pg_query_params($conn, "SELECT 1 FROM \"Students\" WHERE student_id=$1 AND deleted_at IS NOT NULL", [$id]);
                if (pg_num_rows($chk) === 0) throw new Exception('Student not found or is not deleted');

                $sql = "UPDATE \"Students\" SET deleted_at=NULL, deleted_by=NULL, status='active', updated_at=CURRENT_TIMESTAMP WHERE student_id=$1";
                if (!pg_query_params($conn, $sql, [$id])) throw new Exception('Failed to restore student');
                echo json_encode(['success' => true, 'message' => 'Student restored successfully']);
                break;

            default:
                throw new Exception('Unknown action');
        }
        pg_query($conn, "COMMIT");
    } catch (Exception $e) {
        pg_query($conn, "ROLLBACK");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        if (isset($conn)) pg_close($conn);
    }
    exit;
}

// Fetch all students for initial page load
$conn = create_connection();
$res = pg_query($conn, 'SELECT * FROM "Students" ORDER BY created_at DESC');
$students = [];
if ($res) {
    while ($row = pg_fetch_assoc($res)) {
        $students[] = $row;
    }
}
pg_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Intern Connect | Student Manager</title>
  <link rel="icon" href="../static/images/title.png">
  <link rel="stylesheet" href="../static/css/index.css">
  <style>
        /* Modal specific styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.6);
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background-color: #fefefe;
            color: #333;
            margin: auto;
            padding: 25px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 10px;
            position: relative;
        }

        .modal-close {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
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

    <header class="header">STUDENT MANAGER</header>
    <?php require_once '../includes/navigation.php'; echo render_navigation('admin'); ?>

    <div class="content">
        <h1 class="welcome">Manage Students</h1>
        <table class="apps-table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Department</th>
                <th>Year</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?= htmlspecialchars($student['name']) ?></td>
                    <td><?= htmlspecialchars($student['email']) ?></td>
                    <td><?= htmlspecialchars($student['phone'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($student['department'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($student['academic_year'] ?? 'N/A') ?></td>
                    <td>
                        <span class="status-<?= strtolower(htmlspecialchars($student['status'])) ?>">
                            <?= htmlspecialchars($student['status']) ?>
                        </span>
                        <?php if ($student['deleted_at']): ?> (deleted)<?php endif; ?>
                    </td>
                    <td>
                        <button class="btn" onclick="viewStudent('<?= $student['student_id'] ?>')">View</button>
                        <button class="btn" onclick="openEditModal('<?= $student['student_id'] ?>')" <?php if ($student['deleted_at']) echo 'disabled'; ?>>Edit</button>
                        <?php if ($student['status'] === 'active' && !$student['deleted_at']): ?>
                            <button class="btn" onclick="toggleStatus('<?= $student['student_id'] ?>', 'disabled')">Disable</button>
                            <button class="btn" onclick="toggleStatus('<?= $student['student_id'] ?>', 'suspended')">Suspend</button>
                        <?php elseif (in_array($student['status'], ['disabled', 'suspended']) && !$student['deleted_at']): ?>
                            <button class="btn" onclick="toggleStatus('<?= $student['student_id'] ?>', 'active')">Enable</button>
                        <?php endif; ?>
                        <?php if (!$student['deleted_at']): ?>
                            <button class="btn" onclick="deleteStudent('<?= $student['student_id'] ?>')">Delete</button>
                        <?php else: ?>
                            <button class="btn" onclick="restoreStudent('<?= $student['student_id'] ?>')">Restore</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="modal" id="editModal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Student Details</h2>
            <form id="editStudentForm" class="form-modern">
                <input type="hidden" name="student_id" id="edit_student_id">
                <div class="form-group">
                    <label for="edit_name">Name</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                <div class="form-group">
                    <label for="edit_phone">Phone</label>
                    <input type="text" name="phone" id="edit_phone">
                </div>
                <div class="form-group">
                    <label for="edit_department">Department</label>
                    <input type="text" name="department" id="edit_department">
                </div>
                <div class="form-group">
                    <label for="edit_academic_year">Academic Year</label>
                    <input type="text" name="academic_year" id="edit_academic_year">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="modal" id="viewModal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeViewModal()">&times;</span>
            <h2>Student Details</h2>
            <div id="viewStudentContent"></div>
             <div class="form-actions" style="text-align: right; margin-top: 1rem;">
                <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>


    <footer class="footer">Intern Connect &copy; <?= date('Y') ?> | All Rights Reserved</footer>

    <script>
        // Generic API function to handle all fetch requests
        function api(action, data) {
            return fetch(`?action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(response => {
                if (!response.success) {
                    alert('Error: ' + response.message);
                }
                return response;
            })
            .catch(err => {
                alert('An unexpected error occurred. ' + err);
                console.error(err);
            });
        }

        // --- Modal Handling ---
        const editModal = document.getElementById('editModal');
        const viewModal = document.getElementById('viewModal');
        const editForm = document.getElementById('editStudentForm');

        function openEditModal(studentId) {
            api('get_details', { student_id: studentId }).then(res => {
                if (res.success) {
                    const student = res.student;
                    document.getElementById('edit_student_id').value = student.student_id;
                    document.getElementById('edit_name').value = student.name;
                    document.getElementById('edit_email').value = student.email;
                    document.getElementById('edit_phone').value = student.phone;
                    document.getElementById('edit_department').value = student.department;
                    document.getElementById('edit_academic_year').value = student.academic_year;
                    editModal.classList.add('active');
                }
            });
        }
        
        function closeEditModal() {
            editModal.classList.remove('active');
        }

        function closeViewModal() {
            viewModal.classList.remove('active');
        }

        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            api('update_profile', data).then(res => {
                if (res.success) {
                    alert(res.message);
                    location.reload();
                }
            });
        });

        // --- Action Functions ---
        function toggleStatus(id, newStatus) {
            const reason = ['disabled', 'suspended'].includes(newStatus) ? prompt('Please provide a reason:') : '';
            if (['disabled', 'suspended'].includes(newStatus) && !reason) return; // User cancelled prompt

            api('toggle_status', { student_id: id, new_status: newStatus, reason: reason }).then(res => {
                if (res.success) location.reload();
            });
        }

        function deleteStudent(id) {
            const reason = prompt('Please provide a reason for deleting this student:');
            if (!reason) return;

            api('delete', { student_id: id, reason: reason }).then(res => {
                if (res.success) location.reload();
            });
        }

        function restoreStudent(id) {
            if (!confirm('Are you sure you want to restore this student?')) return;
            api('restore', { student_id: id }).then(res => {
                if (res.success) location.reload();
            });
        }

        function viewStudent(id) {
            api('get_details', { student_id: id }).then(res => {
                if (res.success) {
                    const s = res.student;
                    const content = `
                        <p><strong>Name:</strong> ${s.name}</p>
                        <p><strong>Email:</strong> ${s.email}</p>
                        <p><strong>Phone:</strong> ${s.phone || 'N/A'}</p>
                        <p><strong>Department:</strong> ${s.department || 'N/A'}</p>
                        <p><strong>Academic Year:</strong> ${s.academic_year || 'N/A'}</p>
                        <hr>
                        <p><strong>Status:</strong> ${s.status}</p>
                        <p><strong>Joined:</strong> ${s.created_at}</p>
                        ${s.deleted_at ? `<p><strong>Deleted On:</strong> ${s.deleted_at}</p>` : ''}
                        ${s.disabled_at ? `<p><strong>Disabled/Suspended On:</strong> ${s.disabled_at}</p><p><strong>Reason:</strong> ${s.disabled_reason}</p>` : ''}
                    `;
                    document.getElementById('viewStudentContent').innerHTML = content;
                    viewModal.classList.add('active');
                }
            });
        }

        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target == editModal) {
                closeEditModal();
            }
            if (event.target == viewModal) {
                closeViewModal();
            }
        }
    </script>
</body>
</html>