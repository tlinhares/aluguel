<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$conn = db_connect();

$id = sanitize_int($_GET['id'] ?? 0);
$print = !empty($_GET['print']);

if (!$id) die('Recibo não informado.');

$r = db_fetch_one($conn, "
    SELECT r.*, i.nome as inquilino_nome, i.cpf, i.telefone as inq_tel,
           p.nome as proprietario_nome, p.cpf_cnpj as prop_doc, p.telefone as prop_tel,
           im.logradouro, im.numero as im_num, im.bairro, im.cidade, im.estado,
           c.numero as contrato_num, c.dia_vencimento
    FROM recibos r
    JOIN inquilinos i ON i.id = r.inquilino_id
    JOIN imoveis im ON im.id = r.imovel_id
    JOIN proprietarios p ON p.id = im.proprietario_id
    JOIN contratos c ON c.id = r.contrato_id
    WHERE r.id = $id
");

if (!$r) die('Recibo não encontrado.');

$config_nome = get_config($conn, 'empresa_nome');
$config_endereco = get_config($conn, 'empresa_endereco');
$config_telefone = get_config($conn, 'empresa_telefone');
$config_cnpj = get_config($conn, 'empresa_cnpj');

$formas = ['pix'=>'PIX','dinheiro'=>'Dinheiro','transferencia'=>'Transferência','boleto'=>'Boleto','cheque'=>'Cheque','cartao'=>'Cartão'];
$meses = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$comp_parts = explode('-', $r['competencia']);
$comp_label = $meses[(int)$comp_parts[1]] . ' de ' . $comp_parts[0];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo <?= htmlspecialchars($r['numero']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; font-size: 14px; }
        .recibo-container { max-width: 720px; margin: 30px auto; background: #fff; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .recibo-header { background: #1a2035; color: #fff; padding: 20px 28px; display: flex; align-items: center; justify-content: space-between; }
        .recibo-header h1 { font-size: 1.2rem; font-weight: 700; margin: 0; }
        .recibo-number { background: rgba(255,255,255,.15); padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; letter-spacing: 0.5px; }
        .recibo-body { padding: 28px; }
        .section-title { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #6c757d; border-bottom: 1px solid #e9ecef; padding-bottom: 4px; margin: 18px 0 10px; }
        .info-row { display: flex; margin-bottom: 6px; }
        .info-label { width: 140px; font-weight: 600; color: #6c757d; font-size: 0.82rem; flex-shrink: 0; }
        .info-value { color: #212529; font-size: 0.9rem; }
        .valor-box { background: #f0f9f4; border: 2px solid #28a745; border-radius: 8px; padding: 16px 20px; margin: 20px 0; text-align: center; }
        .valor-box .valor-label { font-size: 0.82rem; color: #6c757d; margin-bottom: 4px; }
        .valor-box .valor-num { font-size: 1.8rem; font-weight: 700; color: #28a745; }
        .recibo-footer { background: #f8f9fa; border-top: 1px solid #e9ecef; padding: 16px 28px; }
        .assinaturas { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 40px; }
        .assinatura-line { border-top: 1px solid #212529; padding-top: 6px; text-align: center; font-size: 0.82rem; }
        .no-print { margin-bottom: 20px; }
        @media print {
            body { background: white; }
            .recibo-container { box-shadow: none; border: 1px solid #ccc; margin: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="container" style="max-width:760px; padding:20px 15px">
        <div class="no-print d-flex gap-2 justify-content-end">
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i>Imprimir</button>
            <button onclick="window.close()" class="btn btn-secondary btn-sm">Fechar</button>
        </div>

        <div class="recibo-container">
            <div class="recibo-header">
                <div>
                    <h1><?= htmlspecialchars($config_nome) ?></h1>
                    <small style="opacity:.7;font-size:0.78rem"><?= htmlspecialchars($config_endereco) ?></small>
                </div>
                <div class="recibo-number">RECIBO<br><?= htmlspecialchars($r['numero']) ?></div>
            </div>

            <div class="recibo-body">
                <div class="valor-box">
                    <div class="valor-label">Valor Recebido</div>
                    <div class="valor-num"><?= format_money($r['valor_total']) ?></div>
                    <div class="valor-label mt-1">
                        Referente a: <strong><?= $comp_label ?></strong> |
                        Pago em: <strong><?= format_date($r['data_pagamento']) ?></strong> via <strong><?= $formas[$r['forma_pagamento']] ?? ucfirst($r['forma_pagamento']) ?></strong>
                    </div>
                </div>

                <div class="section-title">Inquilino</div>
                <div class="info-row"><span class="info-label">Nome:</span><span class="info-value"><?= htmlspecialchars($r['inquilino_nome']) ?></span></div>
                <div class="info-row"><span class="info-label">CPF:</span><span class="info-value"><?= htmlspecialchars($r['cpf'] ?? '-') ?></span></div>

                <div class="section-title">Imóvel Locado</div>
                <div class="info-row"><span class="info-label">Endereço:</span><span class="info-value"><?= htmlspecialchars($r['logradouro'] . ', ' . $r['im_num']) ?></span></div>
                <div class="info-row"><span class="info-label">Bairro/Cidade:</span><span class="info-value"><?= htmlspecialchars($r['bairro'] . ' - ' . $r['cidade'] . '/' . $r['estado']) ?></span></div>

                <div class="section-title">Discriminação</div>
                <div class="info-row"><span class="info-label">Contrato Nº:</span><span class="info-value"><?= htmlspecialchars($r['contrato_num']) ?></span></div>
                <div class="info-row"><span class="info-label">Competência:</span><span class="info-value"><?= $comp_label ?></span></div>
                <div class="info-row"><span class="info-label">Valor Aluguel:</span><span class="info-value"><?= format_money($r['valor']) ?></span></div>
                <?php if ($r['multa'] > 0): ?>
                <div class="info-row"><span class="info-label">Multa:</span><span class="info-value"><?= format_money($r['multa']) ?></span></div>
                <?php endif; ?>
                <?php if ($r['juros'] > 0): ?>
                <div class="info-row"><span class="info-label">Juros:</span><span class="info-value"><?= format_money($r['juros']) ?></span></div>
                <?php endif; ?>
                <?php if ($r['desconto'] > 0): ?>
                <div class="info-row"><span class="info-label">Desconto:</span><span class="info-value text-success">- <?= format_money($r['desconto']) ?></span></div>
                <?php endif; ?>
                <div class="info-row"><span class="info-label"><strong>Total Pago:</strong></span><span class="info-value"><strong><?= format_money($r['valor_total']) ?></strong></span></div>

                <div class="section-title">Proprietário / Recebedor</div>
                <div class="info-row"><span class="info-label">Nome:</span><span class="info-value"><?= htmlspecialchars($r['proprietario_nome']) ?></span></div>

                <div class="assinaturas">
                    <div>
                        <div style="height:50px"></div>
                        <div class="assinatura-line"><?= htmlspecialchars($r['proprietario_nome']) ?><br><small>Proprietário / Recebedor</small></div>
                    </div>
                    <div>
                        <div style="height:50px"></div>
                        <div class="assinatura-line"><?= htmlspecialchars($r['inquilino_nome']) ?><br><small>Inquilino / Pagador</small></div>
                    </div>
                </div>
            </div>

            <div class="recibo-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Emitido em <?= date('d/m/Y H:i', strtotime($r['emitido_em'])) ?> | <?= htmlspecialchars($config_nome) ?></small>
                    <small class="text-muted"><?= htmlspecialchars($config_telefone) ?></small>
                </div>
            </div>
        </div>
    </div>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php if ($print): ?>
    <script>window.onload = function() { window.print(); }</script>
    <?php endif; ?>
</body>
</html>
