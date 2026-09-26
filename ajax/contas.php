<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$conn = db_connect();
atualizar_parcelas_atrasadas($conn);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        $where = ['1=1'];
        if (!empty($_GET['status'])) {
            $s = mysqli_real_escape_string($conn, $_GET['status']);
            $where[] = "p.status = '$s'";
        }
        if (!empty($_GET['mes'])) {
            $m = mysqli_real_escape_string($conn, $_GET['mes']);
            $where[] = "p.competencia = '$m'";
        }
        if (!empty($_GET['contrato_id'])) {
            $w = sanitize_int($_GET['contrato_id']);
            $where[] = "p.contrato_id = $w";
        }
        $wstr = implode(' AND ', $where);
        $rows = db_fetch_all($conn, "
            SELECT p.*, c.numero as contrato_num,
                   i.nome as inquilino_nome,
                   CONCAT(im.logradouro, ', ', IFNULL(im.numero,'')) as imovel_end,
                   r.id as recibo_id
            FROM parcelas p
            JOIN contratos c ON c.id = p.contrato_id
            JOIN inquilinos i ON i.id = c.inquilino_id
            JOIN imoveis im ON im.id = c.imovel_id
            LEFT JOIN recibos r ON r.parcela_id = p.id
            WHERE $wstr
            ORDER BY p.data_vencimento ASC
        ");
        json_response(true, '', ['data' => $rows]);

    case 'get':
        $id = sanitize_int($_GET['id'] ?? 0);
        $row = db_fetch_one($conn, "
            SELECT p.*, i.nome as inquilino_nome,
                   CONCAT(im.logradouro, ', ', IFNULL(im.numero,'')) as imovel_end
            FROM parcelas p
            JOIN contratos c ON c.id = p.contrato_id
            JOIN inquilinos i ON i.id = c.inquilino_id
            JOIN imoveis im ON im.id = c.imovel_id
            WHERE p.id = $id
        ");
        if (!$row) json_response(false, 'Não encontrado.');
        // Sugestão de multa/juros (tabela configuracoes) para pagamento hoje
        $row['encargos'] = calcular_encargos($conn, (float)$row['valor'], $row['data_vencimento']);
        json_response(true, '', ['data' => $row]);

    case 'encargos':
        // Recalcula a sugestão quando o usuário muda a data de pagamento
        $id = sanitize_int($_GET['id'] ?? 0);
        $data_pag = $_GET['data_pagamento'] ?? '';
        $row = db_fetch_one($conn, "SELECT valor, data_vencimento FROM parcelas WHERE id = $id");
        if (!$row || !data_valida($data_pag)) json_response(false, 'Dados inválidos.');
        json_response(true, '', ['data' => calcular_encargos($conn, (float)$row['valor'], $row['data_vencimento'], $data_pag)]);

    case 'pagar':
        try {
            $parcela_id = sanitize_int($_POST['parcela_id'] ?? 0);
            $valor_pago = round((float)($_POST['valor_pago'] ?? 0), 2);
            $multa      = round((float)($_POST['multa'] ?? 0), 2);
            $juros      = round((float)($_POST['juros'] ?? 0), 2);
            $desconto   = round((float)($_POST['desconto'] ?? 0), 2);
            $data_pag   = trim($_POST['data_pagamento'] ?? date('Y-m-d'));
            $forma      = $_POST['forma_pagamento'] ?? 'pix';
            $obs        = mysqli_real_escape_string($conn, $_POST['observacoes'] ?? '');

            if ($valor_pago <= 0) throw new RegraNegocioException('Informe o valor do aluguel pago.');
            if ($multa < 0 || $juros < 0 || $desconto < 0) throw new RegraNegocioException('Multa, juros e desconto não podem ser negativos.');
            $valor_total = round($valor_pago + $multa + $juros - $desconto, 2);
            if ($valor_total <= 0) throw new RegraNegocioException('O desconto não pode ser maior que o valor recebido.');
            if (!data_valida($data_pag)) throw new RegraNegocioException('Data de pagamento inválida.');
            if ($data_pag > date('Y-m-d')) throw new RegraNegocioException('A data de pagamento não pode ser futura.');
            if (!in_array($forma, ['dinheiro','pix','transferencia','boleto','cheque','cartao'], true)) throw new RegraNegocioException('Forma de pagamento inválida.');

            $num_recibo = db_transacao($conn, function () use ($conn, $parcela_id, $valor_pago, $multa, $juros, $desconto, $valor_total, $data_pag, $forma, $obs) {
                // Trava a parcela: dois pagamentos simultâneos não geram dois recibos
                $parcela = db_fetch_one($conn, "
                    SELECT p.*, c.imovel_id, c.inquilino_id
                    FROM parcelas p JOIN contratos c ON c.id = p.contrato_id
                    WHERE p.id = $parcela_id FOR UPDATE
                ");
                if (!$parcela) throw new RegraNegocioException('Parcela não encontrada!');
                if (!in_array($parcela['status'], ['pendente', 'atrasado'], true)) {
                    throw new RegraNegocioException('Esta parcela já está ' . ($parcela['status'] === 'pago' ? 'paga' : 'cancelada') . '.');
                }
                if (db_fetch_one($conn, "SELECT id FROM recibos WHERE parcela_id = $parcela_id")) {
                    throw new RegraNegocioException('Já existe recibo para esta parcela.');
                }

                $f = fn($v) => number_format($v, 2, '.', '');
                db_exec($conn, "UPDATE parcelas SET status='pago', valor_pago={$f($valor_pago)}, multa={$f($multa)}, juros={$f($juros)}, desconto={$f($desconto)},
                                data_pagamento='$data_pag', forma_pagamento='$forma', observacoes='$obs' WHERE id=$parcela_id");

                $num_recibo = generate_recibo_numero($conn);
                $num_esc  = mysqli_real_escape_string($conn, $num_recibo);
                $comp_esc = mysqli_real_escape_string($conn, $parcela['competencia']);
                $ctr_id   = (int)$parcela['contrato_id'];
                $inq_id   = (int)$parcela['inquilino_id'];
                $imo_id   = (int)$parcela['imovel_id'];
                // valor do recibo = aluguel efetivamente pago, base do valor_total
                db_exec($conn, "INSERT INTO recibos (numero, parcela_id, contrato_id, inquilino_id, imovel_id, competencia, valor, multa, juros, desconto, valor_total, data_pagamento, forma_pagamento)
                                VALUES ('$num_esc', $parcela_id, $ctr_id, $inq_id, $imo_id, '$comp_esc', {$f($valor_pago)}, {$f($multa)}, {$f($juros)}, {$f($desconto)}, {$f($valor_total)}, '$data_pag', '$forma')");
                return $num_recibo;
            });
            json_response(true, "Pagamento registrado! Recibo $num_recibo gerado.");
        } catch (Throwable $e) {
            json_erro($e);
        }

    case 'cancelar':
        $id = sanitize_int($_POST['id'] ?? 0);
        $parcela = db_fetch_one($conn, "SELECT p.status, r.id as recibo_id FROM parcelas p LEFT JOIN recibos r ON r.parcela_id = p.id WHERE p.id = $id");
        if (!$parcela) json_response(false, 'Parcela não encontrada!');
        if ($parcela['recibo_id'] || !in_array($parcela['status'], ['pendente', 'atrasado'], true)) {
            json_response(false, 'Só é possível cancelar parcelas em aberto (pendentes ou atrasadas).');
        }
        if (db_query($conn, "UPDATE parcelas SET status='cancelado' WHERE id=$id AND status IN ('pendente','atrasado')")) json_response(true, 'Parcela cancelada.');
        json_response(false, 'Erro ao cancelar.');

    default:
        json_response(false, 'Ação inválida.');
}
