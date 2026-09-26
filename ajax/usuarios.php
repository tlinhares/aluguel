<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
if (!is_admin()) json_response(false, 'Acesso negado.');
$conn = db_connect();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Valores aceitos (whitelist) para os ENUMs.
const NIVEIS = ['admin', 'operador'];
const STATUS = ['ativo', 'inativo'];

function campo_usuario() {
    $nome   = trim((string)($_POST['nome'] ?? ''));
    $email  = trim((string)($_POST['email'] ?? ''));
    $nivel  = in_array($_POST['nivel'] ?? '', NIVEIS, true) ? $_POST['nivel'] : 'operador';
    $status = in_array($_POST['status'] ?? '', STATUS, true) ? $_POST['status'] : 'ativo';
    if ($nome === '') json_response(false, 'Informe o nome.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_response(false, 'Informe um e-mail válido.');
    return [$nome, $email, $nivel, $status];
}

function email_em_uso($conn, $email, $exceto = 0) {
    $st = mysqli_prepare($conn, "SELECT id FROM usuarios WHERE email = ? AND id <> ? LIMIT 1");
    mysqli_stmt_bind_param($st, 'si', $email, $exceto);
    mysqli_stmt_execute($st);
    return (bool)mysqli_fetch_assoc(mysqli_stmt_get_result($st));
}

switch ($action) {
    case 'list':
        $rows = db_fetch_all($conn, "SELECT id, nome, email, nivel, status, criado_em FROM usuarios ORDER BY id DESC");
        json_response(true, '', ['data' => $rows]);

    case 'get':
        $id = sanitize_int($_GET['id'] ?? 0);
        $row = db_fetch_one($conn, "SELECT id, nome, email, nivel, status FROM usuarios WHERE id = $id");
        if ($row) json_response(true, '', ['data' => $row]);
        json_response(false, 'Não encontrado.');

    case 'create':
        [$nome, $email, $nivel, $status] = campo_usuario();
        $senha = (string)($_POST['senha'] ?? '');
        if (strlen($senha) < 8) json_response(false, 'A senha precisa de ao menos 8 caracteres.');
        if (email_em_uso($conn, $email)) json_response(false, 'Este e-mail já está cadastrado!');
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        // novo usuário troca a senha definida pelo admin no primeiro acesso
        $st = mysqli_prepare($conn, "INSERT INTO usuarios (nome, email, senha, nivel, status, trocar_senha) VALUES (?, ?, ?, ?, ?, 1)");
        mysqli_stmt_bind_param($st, 'sssss', $nome, $email, $hash, $nivel, $status);
        mysqli_stmt_execute($st);
        json_response(true, 'Usuário criado! No primeiro acesso ele definirá a própria senha.');

    case 'update':
        $id = sanitize_int($_POST['id'] ?? 0);
        [$nome, $email, $nivel, $status] = campo_usuario();
        if (email_em_uso($conn, $email, $id)) json_response(false, 'Este e-mail já está em uso por outro usuário!');
        if ($id === (int)$_SESSION['usuario_id'] && ($nivel !== 'admin' || $status !== 'ativo')) {
            json_response(false, 'Você não pode remover o próprio acesso de administrador.');
        }
        $st = mysqli_prepare($conn, "UPDATE usuarios SET nome = ?, email = ?, nivel = ?, status = ? WHERE id = ?");
        mysqli_stmt_bind_param($st, 'ssssi', $nome, $email, $nivel, $status, $id);
        mysqli_stmt_execute($st);
        if (!empty($_POST['senha'])) {
            $senha = (string)$_POST['senha'];
            if (strlen($senha) < 8) json_response(false, 'Dados salvos, mas a senha precisa de ao menos 8 caracteres.');
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $st = mysqli_prepare($conn, "UPDATE usuarios SET senha = ?, trocar_senha = 1 WHERE id = ?");
            mysqli_stmt_bind_param($st, 'si', $hash, $id);
            mysqli_stmt_execute($st);
        }
        json_response(true, 'Usuário atualizado!');

    case 'delete':
        $id = sanitize_int($_POST['id'] ?? 0);
        if ($id == $_SESSION['usuario_id']) json_response(false, 'Não pode excluir o usuário logado!');
        $st = mysqli_prepare($conn, "DELETE FROM usuarios WHERE id = ?");
        mysqli_stmt_bind_param($st, 'i', $id);
        mysqli_stmt_execute($st);
        json_response(true, 'Usuário excluído!');

    default:
        json_response(false, 'Ação inválida.');
}
