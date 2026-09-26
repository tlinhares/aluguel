<?php
require_once __DIR__ . '/config.php';

function db_connect() {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn) {
        die(json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados: ' . mysqli_connect_error()]));
    }
    mysqli_set_charset($conn, 'utf8mb4');
    return $conn;
}

function db_query($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        error_log("DB Error: " . mysqli_error($conn) . " | SQL: " . $sql);
    }
    return $result;
}

function db_fetch_all($conn, $sql) {
    $result = db_query($conn, $sql);
    $rows = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        mysqli_free_result($result);
    }
    return $rows;
}

function db_fetch_one($conn, $sql) {
    $result = db_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        return $row;
    }
    return null;
}

function db_insert($conn, $sql) {
    if (db_query($conn, $sql)) {
        return mysqli_insert_id($conn);
    }
    return false;
}

function db_escape($conn, $value) {
    return mysqli_real_escape_string($conn, trim($value));
}
