<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$conn = db_connect();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        $rows = db_fetch_all($conn, "
            SELECT r.*, i.nome as inquilino_nome,
                   CONCAT(im.logradouro, ', ', IFNULL(im.numero,'')) as imovel_end
            FROM recibos r
            JOIN inquilinos i ON i.id = r.inquilino_id
            JOIN imoveis im ON im.id = r.imovel_id
            ORDER BY r.id DESC
        ");
        json_response(true, '', ['data' => $rows]);

    case 'get':
        $id = sanitize_int($_GET['id'] ?? 0);
        $row = db_fetch_one($conn, "
            SELECT r.*, i.nome as inquilino_nome, i.cpf, i.telefone,
                   p.nome as proprietario_nome,
                   im.logradouro, im.numero as im_num, im.bairro, im.cidade, im.estado,
                   c.numero as contrato_num
            FROM recibos r
            JOIN inquilinos i ON i.id = r.inquilino_id
            JOIN imoveis im ON im.id = r.imovel_id
            JOIN proprietarios p ON p.id = im.proprietario_id
            JOIN contratos c ON c.id = r.contrato_id
            WHERE r.id = $id
        ");
        if ($row) json_response(true, '', ['data' => $row]);
        json_response(false, 'Não encontrado.');

    default:
        json_response(false, 'Ação inválida.');
}
