<?php
function validate_uuid($uuid) {
    if (empty($uuid)) {
        return false;
    }
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid) === 1;
}

/**
 * Check if user account is active and not deleted
 */
function validate_user_status($user_id, $user_type, $conn = null) {
    if (!$conn) {
        require_once 'connection.php';
        $conn = create_connection();
        $should_close = true;
    } else {
        $should_close = false;
    }
    
    if (!validate_uuid($user_id)) {
        if ($should_close) pg_close($conn);
        return false;
    }
    
    $table = '';
    $id_column = '';
    
    switch ($user_type) {
        case 'student':
            $table = 'Students';
            $id_column = 'student_id';
            break;
        case 'employer':
            $table = 'Employers';
            $id_column = 'employer_id';
            break;
        case 'admin':
            $table = 'Administrators';
            $id_column = 'admin_id';
            if ($should_close) pg_close($conn);
            return true; // Admins don't have status restrictions
        default:
            if ($should_close) pg_close($conn);
            return false;
    }
    
    $sql = "SELECT status, deleted_at FROM \"$table\" WHERE $id_column = $1";
    $result = pg_query_params($conn, $sql, [$user_id]);
    
    if (!$result || pg_num_rows($result) === 0) {
        if ($should_close) pg_close($conn);
        return false;
    }
    
    $user = pg_fetch_assoc($result);
    if ($should_close) pg_close($conn);
    
    // Check if user is deleted (soft delete)
    if ($user['deleted_at'] !== null) {
        return false;
    }
    
    // Check if user status is active
    return $user['status'] === 'active';
}

/**
 * Get user status information
 */
function get_user_status($user_id, $user_type, $conn = null) {
    if (!$conn) {
        require_once 'connection.php';
        $conn = create_connection();
        $should_close = true;
    } else {
        $should_close = false;
    }
    
    if (!validate_uuid($user_id)) {
        if ($should_close) pg_close($conn);
        return null;
    }
    
    $table = '';
    $id_column = '';
    
    switch ($user_type) {
        case 'student':
            $table = 'Students';
            $id_column = 'student_id';
            break;
        case 'employer':
            $table = 'Employers';
            $id_column = 'employer_id';
            break;
        case 'admin':
            $table = 'Administrators';
            $id_column = 'admin_id';
            break;
        default:
            if ($should_close) pg_close($conn);
            return null;
    }
    
    $sql = "SELECT status, disabled_at, disabled_by, disabled_reason, deleted_at, deleted_by 
            FROM \"$table\" WHERE $id_column = $1";
    $result = pg_query_params($conn, $sql, [$user_id]);
    
    if (!$result || pg_num_rows($result) === 0) {
        if ($should_close) pg_close($conn);
        return null;
    }
    
    $status = pg_fetch_assoc($result);
    if ($should_close) pg_close($conn);
    
    return $status;
}

function validate_user_session($required_role = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        return false;
    }
    if (!validate_uuid($_SESSION['user_id'])) {
        return false;
    }
    if ($required_role && $_SESSION['user_type'] !== $required_role) {
        return false;
    }
    
    // Check user status (except for admins)
    if ($_SESSION['user_type'] !== 'admin') {
        if (!validate_user_status($_SESSION['user_id'], $_SESSION['user_type'])) {
            // User has been disabled or deleted, destroy session
            session_destroy();
            return false;
        }
    }
    
    return true;
}

function require_auth($required_role = null, $login_url = '../Authentication/login.php') {
    if (!validate_user_session($required_role)) {
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
        
        // Check if it's a status issue
        if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] !== 'admin') {
            $status = get_user_status($_SESSION['user_id'], $_SESSION['user_type']);
            if ($status) {
                if ($status['deleted_at'] !== null) {
                    $_SESSION['error_message'] = 'Your account has been removed. Please contact support for assistance.';
                } elseif ($status['status'] === 'disabled') {
                    $reason = $status['disabled_reason'] ? ': ' . $status['disabled_reason'] : '';
                    $_SESSION['error_message'] = 'Your account has been disabled' . $reason . '. Please contact support for assistance.';
                } elseif ($status['status'] === 'suspended') {
                    $reason = $status['disabled_reason'] ? ': ' . $status['disabled_reason'] : '';
                    $_SESSION['error_message'] = 'Your account has been suspended' . $reason . '. Please contact support for assistance.';
                } else {
                    $_SESSION['error_message'] = 'Access denied. Please log in again.';
                }
            } else {
                $_SESSION['error_message'] = 'Access denied. Please log in again.';
            }
        } elseif ($required_role) {
            $_SESSION['error_message'] = "Access denied. {$required_role} privileges required.";
        } else {
            $_SESSION['error_message'] = 'Please log in to access this page.';
        }
        
        // Destroy session if user status is invalid
        if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] !== 'admin') {
            session_destroy();
            session_start();
        }
        
        header("Location: {$login_url}");
        exit;
    }
}

function get_current_user_id() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id || !validate_uuid($user_id)) {
        return null;
    }
    return $user_id;
}

function get_current_user_type() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['user_type'] ?? null;
}

function safe_query($conn, $query, $params = []) {
    try {
        if (empty($params)) {
            $result = pg_query($conn, $query);
        } else {
            $result = pg_query_params($conn, $query, $params);
        }
        if (!$result) {
            error_log("Database query failed: " . pg_last_error($conn));
            return false;
        }
        return $result;
    } catch (Exception $e) {
        error_log("Database query exception: " . $e->getMessage());
        return false;
    }
}

function set_flash_message($message, $type = 'info') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

function get_flash_message() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $flash = $_SESSION['flash_message'] ?? null;
    unset($_SESSION['flash_message']);
    return $flash;
}

function safe_output($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function redirect_with_message($url, $message, $type = 'info') {
    set_flash_message($message, $type);
    header("Location: {$url}");
    exit;
}

function withdraw_application($conn, $application_id, $student_id) {
    if (!validate_uuid($application_id) || !validate_uuid($student_id)) {
        return false;
    }
    $query = 'DELETE FROM "Applications" WHERE application_id = $1 AND student_id = $2 AND status = $3';
    $result = safe_query($conn, $query, [$application_id, $student_id, 'Pending']);
    return $result && pg_affected_rows($result) > 0;
}

function is_authenticated() {
    return validate_user_session();
}

/**
 * Log admin action for audit trail
 */
function log_admin_action($admin_id, $action_type, $target_user_id, $target_user_type, $reason = null, $old_values = null, $new_values = null, $conn = null) {
    if (!$conn) {
        require_once 'connection.php';
        $conn = create_connection();
        $should_close = true;
    } else {
        $should_close = false;
    }
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $old_values_json = $old_values ? json_encode($old_values) : null;
    $new_values_json = $new_values ? json_encode($new_values) : null;
    
    $sql = "INSERT INTO \"AdminActions\" (admin_id, action_type, target_user_id, target_user_type, reason, old_values, new_values, ip_address, user_agent) 
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)";
    
    $result = pg_query_params($conn, $sql, [
        $admin_id, $action_type, $target_user_id, $target_user_type, 
        $reason, $old_values_json, $new_values_json, $ip_address, $user_agent
    ]);
    
    if ($should_close) pg_close($conn);
    
    return $result !== false;
}

/**
 * Toggle user status (enable/disable/suspend)
 */
function toggle_user_status($admin_id, $user_id, $user_type, $new_status, $reason = null, $conn = null) {
    if (!$conn) {
        require_once 'connection.php';
        $conn = create_connection();
        $should_close = true;
    } else {
        $should_close = false;
    }
    
    if (!validate_uuid($admin_id) || !validate_uuid($user_id)) {
        if ($should_close) pg_close($conn);
        return false;
    }
    
    if (!in_array($new_status, ['active', 'disabled', 'suspended'])) {
        if ($should_close) pg_close($conn);
        return false;
    }
    
    $table = '';
    $id_column = '';
    
    switch ($user_type) {
        case 'student':
            $table = 'Students';
            $id_column = 'student_id';
            break;
        case 'employer':
            $table = 'Employers';
            $id_column = 'employer_id';
            break;
        default:
            if ($should_close) pg_close($conn);
            return false;
    }
    
    // Get current status for logging
    $current_status = get_user_status($user_id, $user_type, $conn);
    if (!$current_status) {
        if ($should_close) pg_close($conn);
        return false;
    }
    
    // Begin transaction
    pg_query($conn, 'BEGIN');
    
    try {
        // Update user status
        if ($new_status === 'active') {
            $sql = "UPDATE \"$table\" SET 
                    status = $1, 
                    disabled_at = NULL, 
                    disabled_by = NULL, 
                    disabled_reason = NULL, 
                    updated_at = CURRENT_TIMESTAMP 
                    WHERE $id_column = $2 AND deleted_at IS NULL";
            $params = [$new_status, $user_id];
        } else {
            $sql = "UPDATE \"$table\" SET 
                    status = $1, 
                    disabled_at = CURRENT_TIMESTAMP, 
                    disabled_by = $2, 
                    disabled_reason = $3, 
                    updated_at = CURRENT_TIMESTAMP 
                    WHERE $id_column = $4 AND deleted_at IS NULL";
            $params = [$new_status, $admin_id, $reason, $user_id];
        }
        
        $result = pg_query_params($conn, $sql, $params);
        
        if (!$result || pg_affected_rows($result) === 0) {
            pg_query($conn, 'ROLLBACK');
            if ($should_close) pg_close($conn);
            return false;
        }
        
        // Log the action
        $action_type = $new_status === 'active' ? 'enable' : $new_status;
        $old_values = ['status' => $current_status['status']];
        $new_values = ['status' => $new_status];
        
        if (!log_admin_action($admin_id, $action_type, $user_id, $user_type, $reason, $old_values, $new_values, $conn)) {
            pg_query($conn, 'ROLLBACK');
            if ($should_close) pg_close($conn);
            return false;
        }
        
        pg_query($conn, 'COMMIT');
        if ($should_close) pg_close($conn);
        return true;
        
    } catch (Exception $e) {
        pg_query($conn, 'ROLLBACK');
        if ($should_close) pg_close($conn);
        return false;
    }
}

/**
 * Soft delete user
 */
function soft_delete_user($admin_id, $user_id, $user_type, $reason = null, $conn = null) {
    if (!$conn) {
        require_once 'connection.php';
        $conn = create_connection();
        $should_close = true;
    } else {
        $should_close = false;
    }
    
    if (!validate_uuid($admin_id) || !validate_uuid($user_id)) {
        if ($should_close) pg_close($conn);
        return false;
    }
    
    $table = '';
    $id_column = '';
    
    switch ($user_type) {
        case 'student':
            $table = 'Students';
            $id_column = 'student_id';
            break;
        case 'employer':
            $table = 'Employers';
            $id_column = 'employer_id';
            break;
        default:
            if ($should_close) pg_close($conn);
            return false;
    }
    
    // Get current status for logging
    $current_status = get_user_status($user_id, $user_type, $conn);
    if (!$current_status) {
        if ($should_close) pg_close($conn);
        return false;
    }
    
    // Begin transaction
    pg_query($conn, 'BEGIN');
    
    try {
        // Soft delete user
        $sql = "UPDATE \"$table\" SET 
                deleted_at = CURRENT_TIMESTAMP, 
                deleted_by = $1, 
                status = 'disabled', 
                disabled_at = CURRENT_TIMESTAMP, 
                disabled_by = $1, 
                disabled_reason = $2, 
                updated_at = CURRENT_TIMESTAMP 
                WHERE $id_column = $3 AND deleted_at IS NULL";
        
        $result = pg_query_params($conn, $sql, [$admin_id, $reason, $user_id]);
        
        if (!$result || pg_affected_rows($result) === 0) {
            pg_query($conn, 'ROLLBACK');
            if ($should_close) pg_close($conn);
            return false;
        }
        
        // Log the action
        $old_values = ['status' => $current_status['status'], 'deleted_at' => null];
        $new_values = ['status' => 'disabled', 'deleted_at' => date('Y-m-d H:i:s')];
        
        if (!log_admin_action($admin_id, 'delete', $user_id, $user_type, $reason, $old_values, $new_values, $conn)) {
            pg_query($conn, 'ROLLBACK');
            if ($should_close) pg_close($conn);
            return false;
        }
        
        pg_query($conn, 'COMMIT');
        if ($should_close) pg_close($conn);
        return true;
        
    } catch (Exception $e) {
        pg_query($conn, 'ROLLBACK');
        if ($should_close) pg_close($conn);
        return false;
    }
}
/**
 * Update user profile (admin action)
 */
function update_user_profile($admin_id, $user_id, $user_type, $updates, $conn = null) {
    if (!$conn) {
        require_once 'connection.php';
        $conn = create_connection();
        $should_close = true;
    } else {
        $should_close = false;
    }
    
    if (!validate_uuid($admin_id) || !validate_uuid($user_id)) {
        if ($should_close) pg_close($conn);
        return false;
    }
    
    $table = '';
    $id_column = '';
    
    switch ($user_type) {
        case 'student':
            $table = 'Students';
            $id_column = 'student_id';
            break;
        case 'employer':
            $table = 'Employers';
            $id_column = 'employer_id';
            break;
        default:
            if ($should_close) pg_close($conn);
            return false;
    }
    
    if (empty($updates)) {
        if ($should_close) pg_close($conn);
        return false;
    }
    
    // Get current values for logging
    $current_sql = "SELECT * FROM \"$table\" WHERE $id_column = $1";
    $current_result = pg_query_params($conn, $current_sql, [$user_id]);
    
    if (!$current_result || pg_num_rows($current_result) === 0) {
        if ($should_close) pg_close($conn);
        return false;
    }
    
    $current_data = pg_fetch_assoc($current_result);
    
    // Begin transaction
    pg_query($conn, 'BEGIN');
    
    try {
        // Build update query
        $set_clauses = [];
        $params = [];
        $param_count = 0;
        
        foreach ($updates as $column => $value) {
            $set_clauses[] = "$column = $" . (++$param_count);
            $params[] = $value;
        }
        
        $set_clauses[] = "updated_at = CURRENT_TIMESTAMP";
        
        $sql = "UPDATE \"$table\" SET " . implode(', ', $set_clauses) . 
               " WHERE $id_column = $" . (++$param_count);
        $params[] = $user_id;
        
        $result = pg_query_params($conn, $sql, $params);
        
        if (!$result || pg_affected_rows($result) === 0) {
            pg_query($conn, 'ROLLBACK');
            if ($should_close) pg_close($conn);
            return false;
        }
        
        // Log the action
        $old_values = [];
        $new_values = [];
        
        foreach ($updates as $column => $value) {
            $old_values[$column] = $current_data[$column] ?? null;
            $new_values[$column] = $value;
        }
        
        if (!log_admin_action($admin_id, 'edit', $user_id, $user_type, 'Profile updated by admin', $old_values, $new_values, $conn)) {
            pg_query($conn, 'ROLLBACK');
            if ($should_close) pg_close($conn);
            return false;
        }
        
        pg_query($conn, 'COMMIT');
        if ($should_close) pg_close($conn);
        return true;
        
    } catch (Exception $e) {
        pg_query($conn, 'ROLLBACK');
        if ($should_close) pg_close($conn);
        return false;
    }
}
?>
