<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$page_title = 'Relatórios';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="fade-in">
    <div class="page-header">
        <h1><i class="bi bi-bar-chart-fill me-2"></i>Relatórios</h1>
    </div>

    <div class="row g-3 mb-4">
        <!-- Filtros -->
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Período Início</label>
                            <input type="date" class="form-control" id="filtro_inicio" value="<?= date('Y-m-01') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Período Fim</label>
                            <input type="date" class="form-control" id="filtro_fim" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Relatório</label>
                            <select class="form-select" id="tipo_relatorio">
                                <option value="receita">Receita por Período</option>
                                <option value="inadimplencia">Inadimplência</option>
                                <option value="contratos">Contratos Ativos</option>
                                <option value="imoveis_proprietario">Imóveis por Proprietário</option>
                                <option value="manutencoes">Manutenções</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" onclick="gerarRelatorio()">
                                <i class="bi bi-search me-1"></i>Gerar Relatório
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resultado -->
    <div id="resultadoRelatorio" class="d-none">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span id="relatorioTitulo">Relatório</span>
                <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>Imprimir
                </button>
            </div>
            <div class="card-body" id="relatorioConteudo">
                <!-- Conteúdo dinâmico -->
            </div>
        </div>
    </div>
    <div id="loadingRelatorio" class="d-none text-center py-4">
        <div class="spinner-border text-primary"></div>
        <div class="mt-2 text-muted">Gerando relatório...</div>
    </div>
</div>

<?php $extra_js = '<script>
function gerarRelatorio() {
    const tipo = document.getElementById("tipo_relatorio").value;
    const inicio = document.getElementById("filtro_inicio").value;
    const fim = document.getElementById("filtro_fim").value;
    const titulos = {
        receita: "Receita por Período",
        inadimplencia: "Inadimplência",
        contratos: "Contratos Ativos",
        imoveis_proprietario: "Imóveis por Proprietário",
        manutencoes: "Relatório de Manutenções"
    };

    document.getElementById("resultadoRelatorio").classList.add("d-none");
    document.getElementById("loadingRelatorio").classList.remove("d-none");

    fetch(`${window.BASE_URL}/ajax/relatorios.php?action=${tipo}&inicio=${inicio}&fim=${fim}`)
        .then(r => r.json())
        .then(res => {
            document.getElementById("loadingRelatorio").classList.add("d-none");
            if (!res.success) return showToast(res.message || "Erro ao gerar relatório", "danger");
            document.getElementById("relatorioTitulo").textContent = titulos[tipo] || "Relatório";
            document.getElementById("relatorioConteudo").innerHTML = res.html;
            document.getElementById("resultadoRelatorio").classList.remove("d-none");
        })
        .catch(() => {
            document.getElementById("loadingRelatorio").classList.add("d-none");
            showToast("Erro de conexão", "danger");
        });
}
</script>';
require_once __DIR__ . '/../includes/footer.php';
