<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$conn = db_connect();
atualizar_parcelas_atrasadas($conn);

// Stats
$total_contratos_ativos = db_fetch_one($conn, "SELECT COUNT(*) as c FROM contratos WHERE status='ativo'")['c'] ?? 0;
$total_imoveis_disponiveis = db_fetch_one($conn, "SELECT COUNT(*) as c FROM imoveis WHERE status='disponivel'")['c'] ?? 0;
$total_imoveis = db_fetch_one($conn, "SELECT COUNT(*) as c FROM imoveis")['c'] ?? 0;
$total_inquilinos = db_fetch_one($conn, "SELECT COUNT(*) as c FROM inquilinos WHERE status='ativo'")['c'] ?? 0;

$mes_atual = date('Y-m');
$receita_mes = db_fetch_one($conn, "SELECT COALESCE(SUM(valor_pago),0) as total FROM parcelas WHERE status='pago' AND DATE_FORMAT(data_pagamento,'%Y-%m')='$mes_atual'")['total'] ?? 0;
$inadimplentes = db_fetch_one($conn, "SELECT COUNT(DISTINCT contrato_id) as c FROM parcelas WHERE status='atrasado'")['c'] ?? 0;

$manutencoes_abertas = db_fetch_one($conn, "SELECT COUNT(*) as c FROM manutencoes WHERE status IN('aberta','em_andamento')")['c'] ?? 0;

// Próximos vencimentos (7 dias)
$hoje = date('Y-m-d');
$daqui7 = date('Y-m-d', strtotime('+7 days'));
$proximos = db_fetch_all($conn, "
    SELECT p.*, c.numero as contrato_num, i.nome as inquilino_nome, im.logradouro, im.numero as im_num
    FROM parcelas p
    JOIN contratos c ON c.id = p.contrato_id
    JOIN inquilinos i ON i.id = c.inquilino_id
    JOIN imoveis im ON im.id = c.imovel_id
    WHERE p.status = 'pendente' AND p.data_vencimento BETWEEN '$hoje' AND '$daqui7'
    ORDER BY p.data_vencimento ASC
    LIMIT 8
");

// Receita últimos 6 meses para gráfico
$meses_labels = [];
$meses_valores = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $label = date('M/Y', strtotime("-$i months"));
    $val = db_fetch_one($conn, "SELECT COALESCE(SUM(valor_pago),0) as t FROM parcelas WHERE status='pago' AND DATE_FORMAT(data_pagamento,'%Y-%m')='$m'")['t'] ?? 0;
    $meses_labels[] = $label;
    $meses_valores[] = (float)$val;
}

// Imóveis por status para o gráfico de rosca
$imoveis_status = db_fetch_all($conn, "SELECT status, COUNT(*) as c FROM imoveis GROUP BY status");

$page_title = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Dashboard Content -->
<div class="fade-in">
    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card" style="--card-accent:#6366f1">
                <div class="stat-icon" style="background:rgba(99,102,241,0.15);color:#6366f1">
                    <i class="bi bi-file-earmark-text-fill"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Contratos Ativos</div>
                    <div class="stat-value"><?= $total_contratos_ativos ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card" style="--card-accent:#10b981">
                <div class="stat-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Receita do Mês</div>
                    <div class="stat-value"><?= format_money($receita_mes) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card" style="--card-accent:#ef4444">
                <div class="stat-icon" style="background:rgba(239,68,68,0.15);color:#ef4444">
                    <i class="bi bi-exclamation-circle-fill"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Inadimplentes</div>
                    <div class="stat-value"><?= $inadimplentes ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card" style="--card-accent:#f59e0b">
                <div class="stat-icon" style="background:rgba(245,158,11,0.15);color:#f59e0b">
                    <i class="bi bi-house-fill"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Imóveis Disponíveis</div>
                    <div class="stat-value"><?= $total_imoveis_disponiveis ?><span class="stat-sub"> / <?= $total_imoveis ?></span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts & Vencimentos -->
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-bar-chart-fill me-2 text-primary"></i>Receita Últimos 6 Meses
                </div>
                <div class="card-body">
                    <canvas id="chartReceita" height="80"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-pie-chart-fill me-2 text-primary"></i>Imóveis por Status
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="chartImoveis" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de resumo e próximos vencimentos -->
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-fill me-2 text-warning"></i>Próximos Vencimentos (7 dias)
                </div>
                <div class="card-body p-0">
                    <?php if (empty($proximos)): ?>
                    <div class="empty-state py-3">
                        <i class="bi bi-check-circle-fill text-success d-block"></i>
                        <small>Sem vencimentos nos próximos 7 dias</small>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr>
                                <th>Inquilino</th><th>Competência</th><th>Vencimento</th><th>Valor</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($proximos as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['inquilino_nome']) ?></td>
                                <td><?= competencia_label($p['competencia']) ?></td>
                                <td><?= format_date($p['data_vencimento']) ?></td>
                                <td class="text-money"><?= format_money($p['valor']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="row g-3">
                <div class="col-6">
                    <div class="card text-center p-3">
                        <div class="fs-1 mb-1" style="color:#6366f1"><i class="bi bi-people-fill"></i></div>
                        <div class="fs-3 fw-bold"><?= $total_inquilinos ?></div>
                        <div class="text-muted small">Inquilinos Ativos</div>
                        <a href="<?= BASE_URL ?>/pages/inquilinos.php" class="btn btn-sm btn-outline-primary mt-2">Ver todos</a>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card text-center p-3">
                        <div class="fs-1 mb-1" style="color:#f59e0b"><i class="bi bi-tools"></i></div>
                        <div class="fs-3 fw-bold"><?= $manutencoes_abertas ?></div>
                        <div class="text-muted small">Manutenções Abertas</div>
                        <a href="<?= BASE_URL ?>/pages/manutencoes.php" class="btn btn-sm btn-outline-warning mt-2">Ver todas</a>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card p-3">
                        <div class="fw-semibold mb-2"><i class="bi bi-link-45deg me-1"></i>Acesso Rápido</div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="<?= BASE_URL ?>/pages/contratos.php" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg me-1"></i>Novo Contrato
                            </a>
                            <a href="<?= BASE_URL ?>/pages/contas.php" class="btn btn-sm btn-success">
                                <i class="bi bi-cash me-1"></i>Registrar Pagamento
                            </a>
                            <a href="<?= BASE_URL ?>/pages/imoveis.php" class="btn btn-sm btn-warning">
                                <i class="bi bi-house me-1"></i>Imóveis
                            </a>
                            <a href="<?= BASE_URL ?>/pages/relatorios.php" class="btn btn-sm btn-secondary">
                                <i class="bi bi-file-earmark-bar-graph me-1"></i>Relatórios
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '
<script>
    // Gráfico de Receita
    const ctxR = document.getElementById("chartReceita").getContext("2d");
    new Chart(ctxR, {
        type: "bar",
        data: {
            labels: ' . json_encode($meses_labels) . ',
            datasets: [{
                label: "Receita (R$)",
                data: ' . json_encode($meses_valores) . ',
                backgroundColor: "rgba(99,102,241,0.7)",
                borderColor: "#6366f1",
                borderWidth: 2,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: "#e2e8f0" } }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: "#8b9cb6", callback: v => "R$ " + v.toLocaleString("pt-BR") },
                    grid: { color: "rgba(255,255,255,0.05)" }
                },
                x: {
                    ticks: { color: "#8b9cb6" },
                    grid: { color: "rgba(255,255,255,0.05)" }
                }
            }
        }
    });

    // Gráfico Imóveis
    const statusData = ' . json_encode($imoveis_status) . ';
    const statusLabels = { disponivel:"Disponível", alugado:"Alugado", manutencao:"Manutenção", inativo:"Inativo" };
    const statusColors = { disponivel:"#10b981", alugado:"#6366f1", manutencao:"#f59e0b", inativo:"#6b7280" };
    const ctxI = document.getElementById("chartImoveis").getContext("2d");
    new Chart(ctxI, {
        type: "doughnut",
        data: {
            labels: statusData.map(s => statusLabels[s.status] || s.status),
            datasets: [{
                data: statusData.map(s => s.c),
                backgroundColor: statusData.map(s => statusColors[s.status] || "#6b7280"),
                borderWidth: 2,
                borderColor: "#161b27"
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: "bottom",
                    labels: { color: "#e2e8f0", padding: 10, font: { size: 12 } }
                }
            },
            cutout: "65%"
        }
    });
</script>';
require_once __DIR__ . '/includes/footer.php';
