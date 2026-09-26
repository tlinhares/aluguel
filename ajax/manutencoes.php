<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$conn = db_connect();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        $where = ['1=1'];
        if (!empty($_GET['status'])) {
            $s = mysqli_real_escape_string($conn, $_GET['status']);
            $where[] = "m.status = '$s'";
        }
        $wstr = implode(' AND ', $where);
        $rows = db_fetch_all($conn, "
            SELECT m.*, CONCAT(i.logradouro, ', ', IFNULL(i.numero,'')) as imovel_end
            FROM manutencoes m
            LEFT JOIN imoveis i ON i.id = m.imovel_id
            WHERE $wstr
            ORDER BY m.id DESC
        ");
        json_response(true, '', ['data' => $rows]);

    case 'get':
        $id = sanitize_int($_GET['id'] ?? 0);
        $row = db_fetch_one($conn, "SELECT * FROM manutencoes WHERE id = $id");
        if ($row) json_response(true, '', ['data' => $row]);
        json_response(false, 'Não encontrado.');

    case 'create':
        $sql = build_sql($conn, 'insert');
        $id = db_insert($conn, $sql);
        if ($id) json_response(true, 'Manutenção registrada!');
        json_response(false, 'Erro: ' . mysqli_error($conn));

    case 'update':
        $id = sanitize_int($_POST['id'] ?? 0);
        if (db_query($conn, build_sql($conn, 'update', $id))) json_response(true, 'Manutenção atualizada!');
        json_response(false, 'Erro: ' . mysqli_error($conn));

    case 'delete':
        $id = sanitize_int($_POST['id'] ?? 0);
        if (db_query($conn, "DELETE FROM manutencoes WHERE id = $id")) json_response(true, 'Manutenção excluída!');
        json_response(false, 'Erro ao excluir.');

    default:
        json_response(false, 'Ação inválida.');
}

function get_vals($conn) {
    $text = ['titulo','descricao','tipo','prioridade','responsavel','status','observacoes'];
    $dates = ['data_abertura','data_prevista','data_conclusao'];
    $data = [];
    $data['imovel_id'] = sanitize_int($_POST['imovel_id'] ?? 0);
    foreach ($text as $f) $data[$f] = mysqli_real_escape_string($conn, trim($_POST[$f] ?? ''));
    foreach ($dates as $f) {
        $v = trim($_POST[$f] ?? '');
        $data[$f] = !empty($v) ? "'" . mysqli_real_escape_string($conn, $v) . "'" : 'NULL';
    }
    $data['custo'] = number_format((float)($_POST['custo'] ?? 0), 2, '.', '');
    return $data;
}

function build_sql($conn, $type, $id = null) {
    $data = get_vals($conn);
    $date_fields = ['data_abertura','data_prevista','data_conclusao'];
    if ($type === 'insert') {
        $cols = implode(',', array_keys($data));
        $vals = [];
        foreach ($data as $k => $v) {
            $vals[] = in_array($k, $date_fields) ? $v : "'$v'";
        }
        $vals_str = implode(',', $vals);
        return "INSERT INTO manutencoes ($cols) VALUES ($vals_str)";
    }
    $set = [];
    foreach ($data as $k => $v) $set[] = "$k=" . (in_array($k, $date_fields) ? $v : "'$v'");
    return "UPDATE manutencoes SET " . implode(',', $set) . " WHERE id=$id";
}
