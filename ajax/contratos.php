<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$conn = db_connect();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        $rows = db_fetch_all($conn, "
            SELECT c.*, i.nome as inquilino_nome,
                   CONCAT(im.logradouro, ', ', IFNULL(im.numero,'')) as imovel_end
            FROM contratos c
            LEFT JOIN inquilinos i ON i.id = c.inquilino_id
            LEFT JOIN imoveis im ON im.id = c.imovel_id
            ORDER BY c.id DESC
        ");
        json_response(true, '', ['data' => $rows]);

    case 'get':
        $id = sanitize_int($_GET['id'] ?? 0);
        $row = db_fetch_one($conn, "SELECT * FROM contratos WHERE id = $id");
        if ($row) json_response(true, '', ['data' => $row]);
        json_response(false, 'Não encontrado.');

    case 'create':
        try {
            $d = dados_contrato($conn);
            validar_contrato($conn, $d);
            $numero = db_transacao($conn, function () use ($conn, $d) {
                $numero = generate_contrato_numero($conn);
                $numero_esc = mysqli_real_escape_string($conn, $numero);
                db_exec($conn, "INSERT INTO contratos (numero, imovel_id, inquilino_id, data_inicio, data_fim, valor_aluguel, dia_vencimento, caucao, caucao_pago, indices_reajuste, multa_rescisao, status, observacoes)
                        VALUES ('$numero_esc', {$d['imovel_id']}, {$d['inquilino_id']}, '{$d['data_inicio']}', '{$d['data_fim']}', {$d['valor']}, {$d['dia_venc']},
                                {$d['caucao']}, {$d['caucao_pago']}, '{$d['indices']}', {$d['multa']}, '{$d['status']}', '{$d['obs']}')");
                $ctr_id = mysqli_insert_id($conn);
                if (gera_parcelas($d['status'])) {
                    gerar_parcelas($conn, $ctr_id, $d['data_inicio'], $d['data_fim'], (float)$d['valor'], $d['dia_venc']);
                }
                sincronizar_status_imovel($conn, $d['imovel_id']);
                return $numero;
            });
            atualizar_parcelas_atrasadas($conn);
            json_response(true, "Contrato $numero criado com parcelas geradas!");
        } catch (Throwable $e) {
            json_erro($e);
        }

    case 'update':
        try {
            $id = sanitize_int($_POST['id'] ?? 0);
            $antigo = db_fetch_one($conn, "SELECT * FROM contratos WHERE id = $id");
            if (!$antigo) throw new RegraNegocioException('Contrato não encontrado.');
            $d = dados_contrato($conn);
            validar_contrato($conn, $d, $id);

            // Só regenera parcelas se algo que as define mudou (ou se o contrato foi reativado)
            $mudou_parcelas = $d['data_inicio'] !== $antigo['data_inicio']
                || $d['data_fim'] !== $antigo['data_fim']
                || (float)$d['valor'] !== (float)$antigo['valor_aluguel']
                || $d['dia_venc'] !== (int)$antigo['dia_vencimento']
                || !gera_parcelas($antigo['status']);

            db_transacao($conn, function () use ($conn, $d, $id, $antigo, $mudou_parcelas) {
                db_exec($conn, "UPDATE contratos SET imovel_id={$d['imovel_id']}, inquilino_id={$d['inquilino_id']},
                        data_inicio='{$d['data_inicio']}', data_fim='{$d['data_fim']}', valor_aluguel={$d['valor']},
                        dia_vencimento={$d['dia_venc']}, caucao={$d['caucao']}, caucao_pago={$d['caucao_pago']},
                        indices_reajuste='{$d['indices']}', multa_rescisao={$d['multa']}, status='{$d['status']}', observacoes='{$d['obs']}'
                        WHERE id=$id");
                if ($mudou_parcelas && gera_parcelas($d['status'])) {
                    gerar_parcelas($conn, $id, $d['data_inicio'], $d['data_fim'], (float)$d['valor'], $d['dia_venc']);
                }
                sincronizar_status_imovel($conn, $antigo['imovel_id']);
                sincronizar_status_imovel($conn, $d['imovel_id']);
            });
            atualizar_parcelas_atrasadas($conn);
            json_response(true, 'Contrato atualizado!');
        } catch (Throwable $e) {
            json_erro($e);
        }

    case 'delete':
        try {
            $id = sanitize_int($_POST['id'] ?? 0);
            $ctr = db_fetch_one($conn, "SELECT * FROM contratos WHERE id = $id");
            if (!$ctr) throw new RegraNegocioException('Contrato não encontrado.');
            $pagos = db_fetch_one($conn, "
                SELECT COUNT(*) as c FROM parcelas p LEFT JOIN recibos r ON r.parcela_id = p.id
                WHERE p.contrato_id = $id AND (p.status = 'pago' OR r.id IS NOT NULL)
            ");
            if ($pagos && $pagos['c'] > 0) {
                throw new RegraNegocioException('Não é possível excluir: contrato possui pagamentos/recibos. Altere o status para Encerrado ou Rescindido.');
            }
            db_transacao($conn, function () use ($conn, $id, $ctr) {
                db_exec($conn, "DELETE FROM contratos WHERE id = $id"); // parcelas em aberto caem por CASCADE
                sincronizar_status_imovel($conn, $ctr['imovel_id']);
            });
            json_response(true, 'Contrato excluído!');
        } catch (Throwable $e) {
            json_erro($e);
        }

    default:
        json_response(false, 'Ação inválida.');
}

// Contratos encerrados/rescindidos não têm parcelas (re)geradas.
function gera_parcelas($status) {
    return in_array($status, ['ativo', 'pendente'], true);
}

function dados_contrato($conn) {
    return [
        'imovel_id'    => sanitize_int($_POST['imovel_id'] ?? 0),
        'inquilino_id' => sanitize_int($_POST['inquilino_id'] ?? 0),
        'data_inicio'  => trim($_POST['data_inicio'] ?? ''),
        'data_fim'     => trim($_POST['data_fim'] ?? ''),
        'valor'        => number_format((float)($_POST['valor_aluguel'] ?? 0), 2, '.', ''),
        'dia_venc'     => sanitize_int($_POST['dia_vencimento'] ?? 10),
        'caucao'       => number_format((float)($_POST['caucao'] ?? 0), 2, '.', ''),
        'caucao_pago'  => sanitize_int($_POST['caucao_pago'] ?? 0) ? 1 : 0,
        'indices'      => $_POST['indices_reajuste'] ?? 'IGPM',
        'multa'        => number_format((float)($_POST['multa_rescisao'] ?? 0), 2, '.', ''),
        'status'       => $_POST['status'] ?? 'ativo',
        'obs'          => mysqli_real_escape_string($conn, $_POST['observacoes'] ?? ''),
    ];
}

// Lança RegraNegocioException se o contrato for inválido. $id = contrato em edição (0 = novo).
function validar_contrato($conn, $d, $id = 0) {
    if (!data_valida($d['data_inicio']) || !data_valida($d['data_fim'])) {
        throw new RegraNegocioException('Informe datas de início e término válidas.');
    }
    if ($d['data_fim'] <= $d['data_inicio']) {
        throw new RegraNegocioException('A data de término deve ser posterior à data de início.');
    }
    if ((float)$d['valor'] <= 0) throw new RegraNegocioException('Informe o valor do aluguel.');
    if ($d['dia_venc'] < 1 || $d['dia_venc'] > 31) throw new RegraNegocioException('Dia de vencimento deve estar entre 1 e 31.');
    if (!in_array($d['status'], ['ativo','encerrado','rescindido','pendente'], true)) throw new RegraNegocioException('Status inválido.');
    if (!in_array($d['indices'], ['IGPM','IPCA','INPC','fixo'], true)) throw new RegraNegocioException('Índice de reajuste inválido.');

    $inq = db_fetch_one($conn, "SELECT id FROM inquilinos WHERE id = {$d['inquilino_id']}");
    if (!$inq) throw new RegraNegocioException('Selecione um inquilino válido.');

    $imo = db_fetch_one($conn, "SELECT status FROM imoveis WHERE id = {$d['imovel_id']}");
    if (!$imo) throw new RegraNegocioException('Selecione um imóvel válido.');

    if ($d['status'] === 'ativo') {
        if ($imo['status'] === 'inativo') throw new RegraNegocioException('O imóvel selecionado está inativo.');
        $outro = db_fetch_one($conn, "SELECT numero FROM contratos WHERE imovel_id = {$d['imovel_id']} AND status = 'ativo' AND id <> " . (int)$id);
        if ($outro) throw new RegraNegocioException("O imóvel já possui o contrato ativo {$outro['numero']}.");
    }
}
