<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
if (!is_admin()) json_response(false, 'Acesso negado.');
$conn = db_connect();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

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
        $nome   = mysqli_real_escape_string($conn, trim($_POST['nome'] ?? ''));
        $email  = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
        $senha  = md5($_POST['senha'] ?? '');
        $nivel  = mysqli_real_escape_string($conn, $_POST['nivel'] ?? 'operador');
        $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'ativo');
        // Verifica email duplicado
        $chk = db_fetch_one($conn, "SELECT id FROM usuarios WHERE email = '$email'");
        if ($chk) json_response(false, 'Este e-mail já está cadastrado!');
        $id = db_insert($conn, "INSERT INTO usuarios (nome, email, senha, nivel, status) VALUES ('$nome','$email','$senha','$nivel','$status')");
        if ($id) json_response(true, 'Usuário criado com sucesso!');
        json_response(false, 'Erro: ' . mysqli_error($conn));

    case 'update':
        $id     = sanitize_int($_POST['id'] ?? 0);
        $nome   = mysqli_real_escape_string($conn, trim($_POST['nome'] ?? ''));
        $email  = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
        $nivel  = mysqli_real_escape_string($conn, $_POST['nivel'] ?? 'operador');
        $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'ativo');
        // Verifica email duplicado (exceto o próprio)
        $chk = db_fetch_one($conn, "SELECT id FROM usuarios WHERE email = '$email' AND id != $id");
        if ($chk) json_response(false, 'Este e-mail já está em uso por outro usuário!');
        $senha_sql = '';
        if (!empty($_POST['senha'])) {
            $senha = md5($_POST['senha']);
            $senha_sql = ", senha = '$senha'";
        }
        if (db_query($conn, "UPDATE usuarios SET nome='$nome', email='$email', nivel='$nivel', status='$status'$senha_sql WHERE id=$id"))
            json_response(true, 'Usuário atualizado!');
        json_response(false, 'Erro: ' . mysqli_error($conn));

    case 'delete':
        $id = sanitize_int($_POST['id'] ?? 0);
        if ($id == $_SESSION['usuario_id']) json_response(false, 'Não pode excluir o usuário logado!');
        if (db_query($conn, "DELETE FROM usuarios WHERE id = $id")) json_response(true, 'Usuário excluído!');
        json_response(false, 'Erro ao excluir.');

    default:
        json_response(false, 'Ação inválida.');
}
