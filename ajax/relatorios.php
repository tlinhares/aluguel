<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$conn = db_connect();
atualizar_parcelas_atrasadas($conn);
$action = $_GET['action'] ?? '';
$inicio = $_GET['inicio'] ?? '';
$fim = $_GET['fim'] ?? '';
if (!data_valida($inicio)) $inicio = date('Y-m-01');
if (!data_valida($fim)) $fim = date('Y-m-d');

ob_start();

switch ($action) {

    case 'receita':
        $rows = db_fetch_all($conn, "
            SELECT p.competencia, COUNT(*) as qtd,
                   SUM(p.valor_pago) as total_pago, SUM(p.multa) as total_multa,
                   SUM(p.juros) as total_juros, SUM(p.desconto) as total_desconto
            FROM parcelas p
            WHERE p.status = 'pago' AND p.data_pagamento BETWEEN '$inicio' AND '$fim'
            GROUP BY p.competencia
            ORDER BY p.competencia ASC
        ");
        $total = array_sum(array_column($rows, 'total_pago'));
        $total_geral = $total + array_sum(array_column($rows, 'total_multa')) + array_sum(array_column($rows, 'total_juros'))
                     - array_sum(array_column($rows, 'total_desconto'));
        ?>
        <div class="mb-3">
            <strong>Período:</strong> <?= format_date($inicio) ?> a <?= format_date($fim) ?> |
            <strong>Total Recebido:</strong> <span class="text-money"><?= format_money($total_geral) ?></span>
            <small class="text-muted">(aluguéis + multas/juros − descontos)</small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Competência</th><th>Qtd Pagamentos</th><th>Valor Aluguel</th><th>Multas/Juros</th><th>Descontos</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= competencia_label($r['competencia']) ?></td>
                    <td><?= $r['qtd'] ?></td>
                    <td class="text-money"><?= format_money($r['total_pago']) ?></td>
                    <td class="text-money"><?= format_money($r['total_multa'] + $r['total_juros']) ?></td>
                    <td><?= format_money($r['total_desconto']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?><tr><td colspan="5" class="text-center text-muted">Nenhum registro encontrado.</td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr><td colspan="1"><strong>Total</strong></td><td></td><td class="text-money"><strong><?= format_money($total) ?></strong></td><td colspan="2"></td></tr></tfoot>
            </table>
        </div>
        <?php
        break;

    case 'inadimplencia':
        $rows = db_fetch_all($conn, "
            SELECT p.id, p.competencia, p.data_vencimento, p.valor,
                   i.nome as inquilino, i.telefone, im.logradouro, im.numero as im_num,
                   DATEDIFF(NOW(), p.data_vencimento) as dias_atraso
            FROM parcelas p
            JOIN contratos c ON c.id = p.contrato_id
            JOIN inquilinos i ON i.id = c.inquilino_id
            JOIN imoveis im ON im.id = c.imovel_id
            WHERE p.status = 'atrasado'
            ORDER BY p.data_vencimento ASC
        ");
        $total = array_sum(array_column($rows, 'valor'));
        ?>
        <div class="mb-3">
            <strong><?= count($rows) ?></strong> parcelas em atraso |
            <strong>Total a receber:</strong> <span style="color:var(--danger)"><?= format_money($total) ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Inquilino</th><th>Telefone</th><th>Imóvel</th><th>Competência</th><th>Vencimento</th><th>Dias Atraso</th><th>Valor</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['inquilino']) ?></td>
                    <td><?= htmlspecialchars($r['telefone'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['logradouro'] . ', ' . $r['im_num']) ?></td>
                    <td><?= competencia_label($r['competencia']) ?></td>
                    <td><?= format_date($r['data_vencimento']) ?></td>
                    <td><span class="badge bg-danger"><?= $r['dias_atraso'] ?> dias</span></td>
                    <td class="text-money"><?= format_money($r['valor']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?><tr><td colspan="7" class="text-center text-success">✓ Nenhuma inadimplência!</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        break;

    case 'contratos':
        $rows = db_fetch_all($conn, "
            SELECT c.*, i.nome as inquilino, i.telefone,
                   im.logradouro, im.numero as im_num, im.cidade,
                   p.nome as proprietario,
                   (SELECT COUNT(*) FROM parcelas pa WHERE pa.contrato_id = c.id AND pa.status = 'atrasado') as parcelas_atrasadas
            FROM contratos c
            JOIN inquilinos i ON i.id = c.inquilino_id
            JOIN imoveis im ON im.id = c.imovel_id
            JOIN proprietarios p ON p.id = im.proprietario_id
            WHERE c.status = 'ativo'
            ORDER BY c.data_fim ASC
        ");
        ?>
        <div class="mb-3"><strong><?= count($rows) ?></strong> contrato(s) ativo(s)</div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Nº</th><th>Inquilino</th><th>Imóvel</th><th>Proprietário</th><th>Venc.</th><th>Valor</th><th>Término</th><th>Atraso</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['numero']) ?></td>
                    <td><?= htmlspecialchars($r['inquilino']) ?> <br><small class="text-muted"><?= htmlspecialchars($r['telefone']??'') ?></small></td>
                    <td><?= htmlspecialchars($r['logradouro'] . ', ' . $r['im_num']) ?> <br><small class="text-muted"><?= htmlspecialchars($r['cidade']??'') ?></small></td>
                    <td><?= htmlspecialchars($r['proprietario']) ?></td>
                    <td>Dia <?= $r['dia_vencimento'] ?></td>
                    <td class="text-money"><?= format_money($r['valor_aluguel']) ?></td>
                    <td><?= format_date($r['data_fim']) ?></td>
                    <td><?= $r['parcelas_atrasadas'] > 0 ? '<span class="badge bg-danger">' . $r['parcelas_atrasadas'] . ' parcs.</span>' : '<span class="badge bg-success">OK</span>' ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?><tr><td colspan="8" class="text-center text-muted">Nenhum contrato ativo.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        break;

    case 'imoveis_proprietario':
        $props = db_fetch_all($conn, "SELECT * FROM proprietarios WHERE status='ativo' ORDER BY nome");
        foreach ($props as $prop):
            $imoveis = db_fetch_all($conn, "SELECT * FROM imoveis WHERE proprietario_id = {$prop['id']}");
            if (empty($imoveis)) continue;
        ?>
        <div class="mb-4">
            <div class="fw-semibold mb-2 border-bottom pb-1"><?= htmlspecialchars($prop['nome']) ?></div>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Endereço</th><th>Tipo</th><th>Valor Aluguel</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($imoveis as $i): ?>
                    <tr>
                        <td><?= htmlspecialchars($i['logradouro'] . ', ' . $i['numero'] . ' - ' . $i['cidade']) ?></td>
                        <td><?= ucfirst($i['tipo']) ?></td>
                        <td class="text-money"><?= format_money($i['valor_aluguel']) ?></td>
                        <td><?= badge_status_imovel($i['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; break;

    case 'manutencoes':
        $rows = db_fetch_all($conn, "
            SELECT m.*, CONCAT(i.logradouro, ', ', IFNULL(i.numero,'')) as imovel_end
            FROM manutencoes m
            JOIN imoveis i ON i.id = m.imovel_id
            WHERE m.data_abertura BETWEEN '$inicio' AND '$fim'
            ORDER BY m.data_abertura DESC
        ");
        $total_custo = array_sum(array_column($rows, 'custo'));
        ?>
        <div class="mb-3">
            <strong><?= count($rows) ?></strong> manutenção(ões) | <strong>Custo Total:</strong> <span class="text-money"><?= format_money($total_custo) ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Título</th><th>Imóvel</th><th>Tipo</th><th>Prioridade</th><th>Custo</th><th>Abertura</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['titulo']) ?></td>
                    <td><?= htmlspecialchars($r['imovel_end']) ?></td>
                    <td><?= ucfirst($r['tipo']) ?></td>
                    <td><?= badge_prioridade_manutencao($r['prioridade']) ?></td>
                    <td class="text-money"><?= format_money($r['custo']) ?></td>
                    <td><?= format_date($r['data_abertura']) ?></td>
                    <td><?= badge_status_manutencao($r['status']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?><tr><td colspan="7" class="text-center text-muted">Nenhum registro.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        break;

    default:
        echo 'Tipo de relatório inválido.';
}

$html = ob_get_clean();
json_response(true, '', ['html' => $html]);
