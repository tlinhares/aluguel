<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$page_title = 'Contas a Receber';
$filtro_contrato = (int)($_GET['contrato_id'] ?? 0);
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="fade-in">
    <div class="page-header">
        <h1><i class="bi bi-cash-stack me-2"></i>Contas a Receber</h1>
    </div>

    <!-- Filtros -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label mb-1">Status</label>
                    <select class="form-select form-select-sm" id="filtroStatus">
                        <option value="">Todos</option>
                        <option value="pendente">Pendente</option>
                        <option value="atrasado" selected>Atrasado</option>
                        <option value="pago">Pago</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label mb-1">Mês/Ano</label>
                    <input type="month" class="form-control form-control-sm" id="filtroMes" value="<?= date('Y-m') ?>">
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-primary" onclick="carregarTabela()">
                        <i class="bi bi-search me-1"></i>Filtrar
                    </button>
                    <button class="btn btn-sm btn-secondary ms-1" onclick="document.getElementById('filtroStatus').value='';document.getElementById('filtroMes').value='';carregarTabela()">
                        Limpar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tblContas" class="table table-hover">
                    <thead><tr>
                        <th>#</th><th>Inquilino</th><th>Imóvel</th><th>Competência</th>
                        <th>Vencimento</th><th>Valor</th><th>Status</th><th>Ações</th>
                    </tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pagamento -->
<div class="modal fade" id="modalPagamento" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Pagamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPagamento">
                <div class="modal-body">
                    <input type="hidden" name="parcela_id" id="pag_parcela_id">
                    <div class="mb-3">
                        <label class="form-label">Inquilino / Imóvel</label>
                        <div class="fw-semibold" id="pag_info">-</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Valor Original</label>
                            <input type="text" class="form-control" id="pag_valor_orig" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vencimento</label>
                            <input type="text" class="form-control" id="pag_vencimento" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Multa (R$)</label>
                            <input type="number" class="form-control" name="multa" id="pag_multa" min="0" step="0.01" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Juros (R$)</label>
                            <input type="number" class="form-control" name="juros" id="pag_juros" min="0" step="0.01" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Desconto (R$)</label>
                            <input type="number" class="form-control" name="desconto" id="pag_desconto" min="0" step="0.01" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Aluguel Pago (R$) *</label>
                            <input type="number" class="form-control" name="valor_pago" id="pag_valor_pago" min="0.01" step="0.01" required>
                            <div class="form-text">Sem multa/juros, que são informados acima.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data Pagamento *</label>
                            <input type="date" class="form-control" name="data_pagamento" id="pag_data_pagamento" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-12">
                            <div class="alert alert-info py-2 mb-0 d-flex justify-content-between align-items-center">
                                <span>Total recebido <small class="text-muted" id="pag_atraso_info"></small></span>
                                <strong id="pag_total">R$ 0,00</strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Forma de Pagamento</label>
                            <select class="form-select" name="forma_pagamento" id="pag_forma_pagamento">
                                <option value="pix">PIX</option><option value="dinheiro">Dinheiro</option>
                                <option value="transferencia">Transferência</option><option value="boleto">Boleto</option>
                                <option value="cheque">Cheque</option><option value="cartao">Cartão</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" name="observacoes" id="pag_obs" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Confirmar Pagamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $extra_js = '<script>
let dt;
const filtroContrato = ' . $filtro_contrato . ';

function statusBadge(s) {
    const map = { pendente:\'<span class="badge bg-warning text-dark">Pendente</span>\', pago:\'<span class="badge bg-success">Pago</span>\', atrasado:\'<span class="badge bg-danger">Atrasado</span>\', cancelado:\'<span class="badge bg-secondary">Cancelado</span>\' };
    return map[s] || s;
}

function carregarTabela() {
    const status = document.getElementById("filtroStatus").value;
    const mes = document.getElementById("filtroMes").value;
    let url = "" + window.BASE_URL + "/ajax/contas.php?action=list";
    if (status) url += "&status=" + status;
    if (mes) url += "&mes=" + mes;
    if (filtroContrato) url += "&contrato_id=" + filtroContrato;

    if (dt) { dt.destroy(); $("#tblContas tbody").empty(); }
    dt = $("#tblContas").DataTable({
        ...dtDefaults,
        order: [[4, "asc"]],
        ajax: { url, dataSrc: "data" },
        columns: [
            { data: "id", width: "50px" },
            { data: "inquilino_nome", defaultContent: "-" },
            { data: "imovel_end", defaultContent: "-" },
            { data: "competencia", render: d => { const m=["","Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"]; const p=d.split("-"); return m[parseInt(p[1])]+"/"+p[0]; } },
            { data: "data_vencimento", render: d => d ? d.split("-").reverse().join("/") : "-" },
            { data: "valor", render: d => `<span class="text-money">R$ ${parseFloat(d).toLocaleString("pt-BR",{minimumFractionDigits:2})}</span>` },
            { data: "status", render: d => statusBadge(d) },
            { data: "id", orderable: false, render: (id, t, row) => {
                let btns = "";
                const aberta = row.status === "pendente" || row.status === "atrasado";
                if (aberta) {
                    btns += `<button class="btn btn-action btn-outline-success me-1" onclick="abrirPagamento(${id})" title="Registrar Pagamento"><i class="bi bi-cash"></i></button>`;
                }
                if (row.recibo_id) {
                    btns += `<a href="${window.BASE_URL}/pages/recibo_view.php?id=${row.recibo_id}" target="_blank" class="btn btn-action btn-outline-info me-1" title="Ver Recibo"><i class="bi bi-receipt"></i></a>`;
                }
                if (aberta) {
                    btns += `<button class="btn btn-action btn-outline-secondary" onclick="cancelarParcela(${id})" title="Cancelar"><i class="bi bi-x-circle"></i></button>`;
                }
                return btns;
            }}
        ]
    });
}
carregarTabela();

function abrirPagamento(id) {
    fetch("" + window.BASE_URL + "/ajax/contas.php?action=get&id=" + id)
        .then(r => r.json()).then(res => {
            if (!res.success) return showToast(res.message, "danger");
            const d = res.data;
            document.getElementById("pag_parcela_id").value = d.id;
            document.getElementById("pag_info").innerHTML = `<strong>${esc(d.inquilino_nome)}</strong><br><small class="text-muted">${esc(d.imovel_end)}</small>`;
            document.getElementById("pag_valor_orig").value = "R$ " + parseFloat(d.valor).toLocaleString("pt-BR",{minimumFractionDigits:2});
            document.getElementById("pag_vencimento").value = d.data_vencimento ? d.data_vencimento.split("-").reverse().join("/") : "-";
            document.getElementById("pag_valor_pago").value = d.valor;
            document.getElementById("pag_data_pagamento").value = new Date().toLocaleDateString("sv-SE");
            aplicarEncargos(d.encargos);
            document.getElementById("pag_desconto").value = "0";
            document.getElementById("pag_obs").value = "";
            new bootstrap.Modal(document.getElementById("modalPagamento")).show();
        });
}

function aplicarEncargos(e) {
    document.getElementById("pag_multa").value = (e && e.multa || 0).toFixed(2);
    document.getElementById("pag_juros").value = (e && e.juros || 0).toFixed(2);
    document.getElementById("pag_atraso_info").textContent = e && e.dias_atraso > 0 ? "(" + e.dias_atraso + " dia(s) de atraso)" : "";
    atualizarTotal();
}

function atualizarTotal() {
    const v = id => parseFloat(document.getElementById(id).value) || 0;
    const total = v("pag_valor_pago") + v("pag_multa") + v("pag_juros") - v("pag_desconto");
    document.getElementById("pag_total").textContent = "R$ " + total.toLocaleString("pt-BR", {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

["pag_valor_pago", "pag_multa", "pag_juros", "pag_desconto"].forEach(id =>
    document.getElementById(id).addEventListener("input", atualizarTotal));

// Mudou a data de pagamento: recalcula a multa/juros sugeridos
document.getElementById("pag_data_pagamento").addEventListener("change", function() {
    const id = document.getElementById("pag_parcela_id").value;
    if (!id || !this.value) return;
    fetch(window.BASE_URL + "/ajax/contas.php?action=encargos&id=" + id + "&data_pagamento=" + this.value)
        .then(r => r.json()).then(res => { if (res.success) aplicarEncargos(res.data); });
});

function cancelarParcela(id) {
    confirmDelete("Deseja cancelar esta parcela?", function() {
        ajaxPost("" + window.BASE_URL + "/ajax/contas.php", { action: "cancelar", id }, function(err, res) {
            if (err || !res) return showToast("Erro de conexão", "danger");
            showToast(res.message, res.success ? "success" : "danger");
            if (res.success) carregarTabela();
        });
    });
}

document.getElementById("formPagamento").addEventListener("submit", function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const data = {};
    fd.forEach((v, k) => data[k] = v);
    data.action = "pagar";
    ajaxPost("" + window.BASE_URL + "/ajax/contas.php", data, function(err, res) {
        if (err || !res) return showToast("Erro de conexão", "danger");
        showToast(res.message, res.success ? "success" : "danger");
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById("modalPagamento")).hide();
            carregarTabela();
        }
    });
});
</script>';
require_once __DIR__ . '/../includes/footer.php';
