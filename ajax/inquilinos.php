<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$conn = db_connect();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        $rows = db_fetch_all($conn, "SELECT * FROM inquilinos ORDER BY id DESC");
        json_response(true, '', ['data' => $rows]);

    case 'get':
        $id = sanitize_int($_GET['id'] ?? 0);
        $row = db_fetch_one($conn, "SELECT * FROM inquilinos WHERE id = $id");
        if ($row) json_response(true, '', ['data' => $row]);
        json_response(false, 'Não encontrado.');

    case 'create':
        $conn = db_connect();
        $id = db_insert($conn, build_insert_sql($conn));
        if ($id) json_response(true, 'Inquilino cadastrado com sucesso!');
        json_response(false, 'Erro ao cadastrar: ' . mysqli_error($conn));

    case 'update':
        $conn = db_connect();
        $id = sanitize_int($_POST['id'] ?? 0);
        $sql = build_update_sql($conn, $id);
        if (db_query($conn, $sql)) json_response(true, 'Inquilino atualizado com sucesso!');
        json_response(false, 'Erro ao atualizar: ' . mysqli_error($conn));

    case 'delete':
        $conn = db_connect();
        $id = sanitize_int($_POST['id'] ?? 0);
        // Verifica se tem contratos
        $chk = db_fetch_one($conn, "SELECT COUNT(*) as c FROM contratos WHERE inquilino_id = $id");
        if ($chk && $chk['c'] > 0) json_response(false, 'Não é possível excluir: inquilino possui contratos!');
        if (db_query($conn, "DELETE FROM inquilinos WHERE id = $id")) json_response(true, 'Inquilino excluído!');
        json_response(false, 'Erro ao excluir.');

    default:
        json_response(false, 'Ação inválida.');
}

function build_insert_sql($conn) {
    $f = get_fields($conn);
    return "INSERT INTO inquilinos ({$f['cols']}) VALUES ({$f['vals']})";
}

function build_update_sql($conn, $id) {
    $fields = get_field_values($conn);
    $set = [];
    foreach ($fields as $col => $val) $set[] = "$col = '$val'";
    $set_str = implode(', ', $set);
    return "UPDATE inquilinos SET $set_str WHERE id = $id";
}

function get_field_values($conn) {
    $allowed = ['nome','cpf','rg','telefone','email','profissao','estado_civil','renda','status','cep','logradouro','numero','complemento','bairro','cidade','estado','observacoes'];
    $data = [];
    foreach ($allowed as $f) {
        $val = $_POST[$f] ?? '';
        $data[$f] = mysqli_real_escape_string($conn, trim($val));
    }
    $data['renda'] = number_format((float)str_replace(',','.', $_POST['renda'] ?? '0'), 2, '.', '');
    return $data;
}

function get_fields($conn) {
    $data = get_field_values($conn);
    $cols = implode(', ', array_keys($data));
    $vals = "'" . implode("', '", array_values($data)) . "'";
    return ['cols' => $cols, 'vals' => $vals];
}
