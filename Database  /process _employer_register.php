<?php

$db_config = [
    'host' => getenv("DB_HOST") ?: 'intern-db',
    'port' => getenv("DB_PORT") ?: '5432',
    'dbname' => getenv("DB_NAME") ?: 'intern-db',
    'user' => getenv("DB_USER") ?: 'intern-user',
    'password' => getenv("DB_PASS") ?: 'intern-pass'
];

function create_connection() {
    global $db_config;
    
    $connection_string = sprintf(
        "host=%s port=%s dbname=%s user=%s password=%s",
        $db_config['host'],
        $db_config['port'],
        $db_config['dbname'],
        $db_config['user'],
        $db_config['password']
    );
    
    try {
        $connection = pg_connect($connection_string);
        
        if (!$connection) {
            error_log("Database connection failed: " . pg_last_error());
            return false;
        }
        
        pg_set_client_encoding($connection, "UTF8");
        
        return $connection;
        
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        return false;
    }
}

function close_connection($connection) {
    if (is_resource($connection)) {
        pg_close($connection);
    }
}


function execute_query($connection, $query, $params = []) {
    if (!is_resource($connection)) {
        error_log("Invalid database connection provided to execute_query");
        return false;
    }
    
    try {
        if (empty($params)) {
            $result = pg_query($connection, $query);
        } else {
            $result = pg_query_params($connection, $query, $params);
        }
        
        if (!$result) {
            error_log("Query execution failed: " . pg_last_error($connection));
            error_log("Query: " . $query);
            return false;
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Query execution error: " . $e->getMessage());
        return false;
    }
}

function fetch_single($connection, $query, $params = []) {
    $result = execute_query($connection, $query, $params);
    
    if (!$result) {
        return false;
    }
    
    $row = pg_fetch_assoc($result);
    pg_free_result($result);
    
    return $row;
}


function fetch_all($connection, $query, $params = []) {
    $result = execute_query($connection, $query, $params);
    
    if (!$result) {
        return false;
    }
    
    $rows = [];
    while ($row = pg_fetch_assoc($result)) {
        $rows[] = $row;
    }
    
    pg_free_result($result);
    return $rows;
}


function get_count($connection, $query, $params = []) {
    $result = execute_query($connection, $query, $params);
    
    if (!$result) {
        return false;
    }
    
    $count = pg_num_rows($result);
    pg_free_result($result);
    
    return $count;
}

function insert_record($connection, $table, $data, $id_column = 'id') {
    if (empty($data)) {
        return false;
    }
    
    $columns = array_keys($data);
    $placeholders = array_map(function($i) { return '$' . ($i + 1); }, array_keys($columns));
    
    $query = sprintf(
        "INSERT INTO %s (%s) VALUES (%s) RETURNING %s",
        pg_escape_identifier($connection, $table),
        implode(', ', array_map(function($col) use ($connection) { 
            return pg_escape_identifier($connection, $col); 
        }, $columns)),
        implode(', ', $placeholders),
        pg_escape_identifier($connection, $id_column)
    );
    
    $result = execute_query($connection, $query, array_values($data));
    
    if (!$result) {
        return false;
    }
    
    $row = pg_fetch_assoc($result);
    pg_free_result($result);
    
    return $row[$id_column] ?? false;
}


function update_record($connection, $table, $data, $where_column, $where_value) {
    if (empty($data)) {
        return false;
    }
    
    $set_clauses = [];
    $values = [];
    $param_counter = 1;
    
    foreach ($data as $column => $value) {
        $set_clauses[] = pg_escape_identifier($connection, $column) . ' = $' . $param_counter;
        $values[] = $value;
        $param_counter++;
    }
    
    $values[] = $where_value; // Add WHERE value as last parameter
    
    $query = sprintf(
        "UPDATE %s SET %s WHERE %s = $%d",
        pg_escape_identifier($connection, $table),
        implode(', ', $set_clauses),
        pg_escape_identifier($connection, $where_column),
        $param_counter
    );
    
    $result = execute_query($connection, $query, $values);
    
    return $result !== false;
}


function delete_record($connection, $table, $where_column, $where_value) {
    $query = sprintf(
        "DELETE FROM %s WHERE %s = $1",
        pg_escape_identifier($connection, $table),
        pg_escape_identifier($connection, $where_column)
    );
    
    $result = execute_query($connection, $query, [$where_value]);
    
    return $result !== false;
}


function begin_transaction($connection) {
    return execute_query($connection, "BEGIN") !== false;
}


function commit_transaction($connection) {
    return execute_query($connection, "COMMIT") !== false;
}

function rollback_transaction($connection) {
    return execute_query($connection, "ROLLBACK") !== false;
}

function table_exists($connection, $table_name) {
    $query = "SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = $1
    )";
    
    $result = fetch_single($connection, $query, [$table_name]);
    
    return $result && $result['exists'] === 't';
}

function get_connection_info($connection) {
    if (!is_resource($connection)) {
        return ['status' => 'Invalid connection'];
    }
    
    return [
        'status' => pg_connection_status($connection) === PGSQL_CONNECTION_OK ? 'Connected' : 'Disconnected',
        'host' => pg_host($connection),
        'port' => pg_port($connection),
        'dbname' => pg_dbname($connection),
        'user' => pg_user($connection),
        'version' => pg_version($connection)
    ];
}

// Global error handler for database operations
function handle_db_error($message, $query = null) {
    $error_msg = "Database Error: " . $message;
    if ($query) {
        $error_msg .= " | Query: " . $query;
    }
    error_log($error_msg);
    
  
    if (getenv('APP_ENV') === 'production') {
        return "A database error occurred. Please try again later.";
    }
    
    return $error_msg;
}

if (getenv('APP_ENV') !== 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

if (!ini_get('default_charset')) {
    ini_set('default_charset', 'UTF-8');
}

if (getenv('APP_ENV') !== 'production') {
    $test_connection = create_connection();
    if ($test_connection) {
        error_log("Database connection successful");
        close_connection($test_connection);
    } else {
        error_log("Database connection failed during initialization");
    }
}
?>
