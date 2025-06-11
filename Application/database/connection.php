<?php

$host = getenv("DB_HOST") ?? 'intern-db';
$port =  getenv("DB_PORT") ?? '5432';
$dbname =  getenv("DB_NAME") ?? 'intern-db';
$username =  getenv("DB_USER") ?? 'intern-user';
$password =  getenv("DB_PASS") ?? 'intern-pass';

function create_connection(){
    global $host, $port, $dbname, $username, $password;

    $connection_string = "host=$host port=$port dbname=$dbname user=$username password=$password";
    $connection = pg_connect($connection_string);
    if (!$connection) {
        die("Connection failed");
    }

    return $connection;
}

create_connection();


?>