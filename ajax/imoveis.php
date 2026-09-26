<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$conn = db_connect();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        $rows = db_fetch_all($conn, "
            SELECT i.*, p.nome as proprietario_nome
            FROM imoveis i
            LEFT JOIN proprietarios p ON p.id = i.proprietario_id
            ORDER BY i.id DESC
        ");
        json_response(true, '', ['data' => $rows]);

    case 'get':
        $id = sanitize_int($_GET['id'] ?? 0);
        $row = db_fetch_one($conn, "SELECT * FROM imoveis WHERE id = $id");
        if ($row) json_response(true, '', ['data' => $row]);
        json_response(false, 'Não encontrado.');

    case 'select':
        $rows = db_fetch_all($conn, "SELECT id, CONCAT(logradouro, ', ', IFNULL(numero,'')) as label, valor_aluguel FROM imoveis WHERE status = 'disponivel' ORDER BY logradouro");
        json_response(true, '', ['data' => $rows]);

    case 'create':
        $id = db_insert($conn, build_sql($conn, 'insert'));
        if ($id) json_response(true, 'Imóvel cadastrado!');
        json_response(false, 'Erro: ' . mysqli_error($conn));

    case 'update':
        $id = sanitize_int($_POST['id'] ?? 0);
        if (db_query($conn, build_sql($conn, 'update', $id))) json_response(true, 'Imóvel atualizado!');
        json_response(false, 'Erro: ' . mysqli_error($conn));

    case 'delete':
        $id = sanitize_int($_POST['id'] ?? 0);
        // Qualquer contrato (inclusive encerrado) impede a exclusão: a FK de contratos é RESTRICT
        $chk = db_fetch_one($conn, "SELECT COUNT(*) as c FROM contratos WHERE imovel_id = $id");
        if ($chk && $chk['c'] > 0) json_response(false, 'Não é possível excluir: imóvel possui contratos vinculados! Altere o status para Inativo.');
        $chk = db_fetch_one($conn, "SELECT COUNT(*) as c FROM manutencoes WHERE imovel_id = $id");
        if ($chk && $chk['c'] > 0) json_response(false, 'Não é possível excluir: imóvel possui manutenções registradas! Altere o status para Inativo.');
        if (db_query($conn, "DELETE FROM imoveis WHERE id = $id")) json_response(true, 'Imóvel excluído!');
        json_response(false, 'Erro ao excluir.');

    default:
        json_response(false, 'Ação inválida.');
}

function get_vals($conn) {
    $allowed = ['proprietario_id','tipo','status','descricao','cep','logradouro','numero','complemento','bairro','cidade','estado','observacoes'];
    $nums = ['area','quartos','banheiros','vagas','valor_aluguel','valor_condominio','valor_iptu'];
    $data = [];
    foreach ($allowed as $f) $data[$f] = mysqli_real_escape_string($conn, trim($_POST[$f] ?? ''));
    foreach ($nums as $f) $data[$f] = number_format((float)($_POST[$f] ?? 0), 2, '.', '');
    return $data;
}

function build_sql($conn, $type, $id = null) {
    $data = get_vals($conn);
    if ($type === 'insert') {
        return "INSERT INTO imoveis (" . implode(',', array_keys($data)) . ") VALUES ('" . implode("','", array_values($data)) . "')";
    }
    $set = []; foreach ($data as $k => $v) $set[] = "$k='$v'";
    return "UPDATE imoveis SET " . implode(',', $set) . " WHERE id=$id";
}
