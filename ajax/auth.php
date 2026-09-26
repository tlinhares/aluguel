<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$action = $_POST['action'] ?? '';

if ($action === 'login') {
    $email = sanitize($conn = db_connect(), $_POST['email'] ?? '');
    $senha = md5($_POST['senha'] ?? '');

    $row = db_fetch_one($conn, "SELECT * FROM usuarios WHERE email = '$email' AND senha = '$senha' AND status = 'ativo'");

    if ($row) {
        $_SESSION['usuario_id']    = $row['id'];
        $_SESSION['usuario_nome']  = $row['nome'];
        $_SESSION['usuario_email'] = $row['email'];
        $_SESSION['usuario_nivel'] = $row['nivel'];
        json_response(true, 'Login realizado com sucesso!');
    } else {
        json_response(false, 'E-mail ou senha incorretos.');
    }
}

json_response(false, 'Ação inválida.');
