<?php
$host = getenv("DB_HOST") ?? 'intern-db';
$port =  getenv("DB_PORT") ?? '5432';
$dbname =  getenv("DB_NAME") ?? 'intern-db';
$username =  getenv("DB_USER") ?? 'intern-user';
$db_password =  getenv("DB_PASS") ?? 'intern-pass';

function create_connection(){
    global $host, $port, $dbname, $username, $db_password;
    $connection_string = "host=$host port=$port dbname=$dbname user=$username password=$db_password";
    $connection = pg_connect($connection_string);
    if (!$connection) {
        die("Connection failed");
    }
    return $connection;
}
?>