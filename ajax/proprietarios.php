<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$conn = db_connect();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        $rows = db_fetch_all($conn, "
            SELECT p.*, (SELECT COUNT(*) FROM imoveis i WHERE i.proprietario_id = p.id) as total_imoveis
            FROM proprietarios p ORDER BY p.id DESC
        ");
        json_response(true, '', ['data' => $rows]);

    case 'get':
        $id = sanitize_int($_GET['id'] ?? 0);
        $row = db_fetch_one($conn, "SELECT * FROM proprietarios WHERE id = $id");
        if ($row) json_response(true, '', ['data' => $row]);
        json_response(false, 'Não encontrado.');

    case 'create':
        $id = db_insert($conn, build_sql($conn, 'insert'));
        if ($id) json_response(true, 'Proprietário cadastrado!');
        json_response(false, 'Erro ao salvar. Verifique os dados e tente novamente.');

    case 'update':
        $id = sanitize_int($_POST['id'] ?? 0);
        if (db_query($conn, build_sql($conn, 'update', $id))) json_response(true, 'Proprietário atualizado!');
        json_response(false, 'Erro ao salvar. Verifique os dados e tente novamente.');

    case 'delete':
        $id = sanitize_int($_POST['id'] ?? 0);
        $chk = db_fetch_one($conn, "SELECT COUNT(*) as c FROM imoveis WHERE proprietario_id = $id");
        if ($chk && $chk['c'] > 0) json_response(false, 'Não é possível excluir: proprietário possui imóveis!');
        if (db_query($conn, "DELETE FROM proprietarios WHERE id = $id")) json_response(true, 'Proprietário excluído!');
        json_response(false, 'Erro ao excluir.');

    default:
        json_response(false, 'Ação inválida.');
}

function get_vals($conn) {
    $allowed = ['nome','cpf_cnpj','rg','telefone','email','status','cep','logradouro','numero','complemento','bairro','cidade','estado','banco','agencia','conta','tipo_conta','pix','observacoes'];
    $data = [];
    foreach ($allowed as $f) $data[$f] = mysqli_real_escape_string($conn, trim($_POST[$f] ?? ''));
    return $data;
}

function build_sql($conn, $type, $id = null) {
    $data = get_vals($conn);
    if ($type === 'insert') {
        $cols = implode(', ', array_keys($data));
        $vals = "'" . implode("', '", array_values($data)) . "'";
        return "INSERT INTO proprietarios ($cols) VALUES ($vals)";
    }
    $set = [];
    foreach ($data as $k => $v) $set[] = "$k = '$v'";
    return "UPDATE proprietarios SET " . implode(', ', $set) . " WHERE id = $id";
}
