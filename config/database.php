<?php
// config/database.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Mell_1506');           // Sesuaikan dengan password MariaDB kamu
define('DB_NAME', 'billing_isp');
define('DB_PORT', 3306);

function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($conn->connect_error) {
            error_log('DB Error: ' . $conn->connect_error);
            die(json_encode(['error' => 'Koneksi database gagal.']));
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}
