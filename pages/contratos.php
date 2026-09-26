<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$page_title = 'Contratos';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="fade-in">
    <div class="page-header">
        <h1><i class="bi bi-file-earmark-text-fill me-2"></i>Contratos</h1>
        <button class="btn btn-primary" id="btnNovoContrato"><i class="bi bi-plus-lg me-1"></i>Novo Contrato</button>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tblContratos" class="table table-hover">
                    <thead><tr>
                        <th>Número</th><th>Inquilino</th><th>Imóvel</th><th>Início</th>
                        <th>Fim</th><th>Valor</th><th>Status</th><th>Ações</th>
                    </tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Contrato -->
<div class="modal fade" id="modalContrato" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalContratoTitle">Novo Contrato</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formContrato">
                <div class="modal-body">
                    <input type="hidden" name="id" id="ctr_id">
                    <div class="section-title" style="margin-top:0">Partes</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Inquilino *</label>
                            <select class="form-select" name="inquilino_id" id="ctr_inquilino_id" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Imóvel *</label>
                            <select class="form-select" name="imovel_id" id="ctr_imovel_id" required></select>
                        </div>
                    </div>
                    <div class="section-title">Dados do Contrato</div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Data Início *</label>
                            <input type="date" class="form-control" name="data_inicio" id="ctr_data_inicio" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Data Fim *</label>
                            <input type="date" class="form-control" name="data_fim" id="ctr_data_fim" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Valor Aluguel (R$) *</label>
                            <input type="number" class="form-control" name="valor_aluguel" id="ctr_valor_aluguel" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Dia Vencimento *</label>
                            <input type="number" class="form-control" name="dia_vencimento" id="ctr_dia_vencimento" min="1" max="31" value="10" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Caução (R$)</label>
                            <input type="number" class="form-control" name="caucao" id="ctr_caucao" min="0" step="0.01" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Caução Pago</label>
                            <select class="form-select" name="caucao_pago" id="ctr_caucao_pago">
                                <option value="0">Não</option><option value="1">Sim</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Índice Reajuste</label>
                            <select class="form-select" name="indices_reajuste" id="ctr_indices_reajuste">
                                <option value="IGPM">IGPM</option><option value="IPCA">IPCA</option>
                                <option value="INPC">INPC</option><option value="fixo">Fixo</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Multa Rescisão (%)</label>
                            <input type="number" class="form-control" name="multa_rescisao" id="ctr_multa_rescisao" min="0" step="0.01" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="ctr_status">
                                <option value="ativo">Ativo</option><option value="pendente">Pendente</option>
                                <option value="encerrado">Encerrado</option><option value="rescindido">Rescindido</option>
                            </select>
                        </div>
                    </div>
                    <div class="section-title">Observações</div>
                    <textarea class="form-control" name="observacoes" id="ctr_observacoes" rows="2"></textarea>
                    <div class="alert alert-info mt-3 mb-0 p-2">
                        <small><i class="bi bi-info-circle me-1"></i>Ao salvar, as parcelas mensais serão geradas automaticamente.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $extra_js = '<script>
let dt;
const statusB = { ativo:\'<span class="badge bg-success">Ativo</span>\', encerrado:\'<span class="badge bg-secondary">Encerrado</span>\', rescindido:\'<span class="badge bg-danger">Rescindido</span>\', pendente:\'<span class="badge bg-warning text-dark">Pendente</span>\' };

function carregarSelects() {
    fetch("" + window.BASE_URL + "/ajax/inquilinos.php?action=list").then(r=>r.json()).then(res=>{
        const sel = document.getElementById("ctr_inquilino_id");
        sel.innerHTML = \'<option value="">Selecione...</option>\';
        (res.data||[]).filter(i=>i.status==="ativo").forEach(i => sel.innerHTML += `<option value="${i.id}">${i.nome}</option>`);
    });
    fetch("" + window.BASE_URL + "/ajax/imoveis.php?action=list").then(r=>r.json()).then(res=>{
        const sel = document.getElementById("ctr_imovel_id");
        sel.innerHTML = \'<option value="">Selecione...</option>\';
        (res.data||[]).filter(i=>i.status!=="inativo").forEach(i => sel.innerHTML += `<option value="${i.id}">[${i.status.toUpperCase()}] ${i.logradouro}, ${i.numero||""} - ${i.cidade||""}</option>`);
    });
}
carregarSelects();

function carregarTabela() {
    if (dt) { dt.destroy(); $("#tblContratos tbody").empty(); }
    dt = $("#tblContratos").DataTable({
        ...dtDefaults,
        ajax: { url: "" + window.BASE_URL + "/ajax/contratos.php?action=list", dataSrc: "data" },
        columns: [
            { data: "numero" },
            { data: "inquilino_nome", defaultContent: "-" },
            { data: "imovel_end", defaultContent: "-" },
            { data: "data_inicio", render: d => d ? d.split("-").reverse().join("/") : "-" },
            { data: "data_fim", render: d => d ? d.split("-").reverse().join("/") : "-" },
            { data: "valor_aluguel", render: d => `<span class="text-money">R$ ${parseFloat(d).toLocaleString("pt-BR",{minimumFractionDigits:2})}</span>` },
            { data: "status", render: d => statusB[d] || d },
            { data: "id", orderable: false, render: (id, t, row) =>
                `<button class="btn btn-action btn-outline-primary me-1" onclick="editar(${id})"><i class="bi bi-pencil"></i></button>
                 <a href="${window.BASE_URL}/pages/contas.php?contrato_id=${id}" class="btn btn-action btn-outline-success me-1" title="Ver parcelas"><i class="bi bi-cash"></i></a>
                 <button class="btn btn-action btn-outline-danger" onclick="excluir(${id})"><i class="bi bi-trash"></i></button>`
            }
        ]
    });
}
carregarTabela();

document.getElementById("btnNovoContrato").addEventListener("click", function() {
    document.getElementById("modalContratoTitle").textContent = "Novo Contrato";
    document.getElementById("formContrato").reset();
    document.getElementById("ctr_id").value = "";
    new bootstrap.Modal(document.getElementById("modalContrato")).show();
});

function editar(id) {
    fetch("" + window.BASE_URL + "/ajax/contratos.php?action=get&id=" + id)
        .then(r => r.json()).then(res => {
            if (!res.success) return showToast(res.message, "danger");
            const d = res.data;
            document.getElementById("modalContratoTitle").textContent = "Editar Contrato";
            const fields = ["id","inquilino_id","imovel_id","data_inicio","data_fim","valor_aluguel","dia_vencimento","caucao","caucao_pago","indices_reajuste","multa_rescisao","status","observacoes"];
            fields.forEach(f => { const el = document.getElementById("ctr_" + f); if (el) el.value = d[f] || ""; });
            new bootstrap.Modal(document.getElementById("modalContrato")).show();
        });
}

function excluir(id) {
    confirmDelete("Deseja encerrar/excluir este contrato?", function() {
        ajaxPost("" + window.BASE_URL + "/ajax/contratos.php", { action: "delete", id }, function(err, res) {
            if (err || !res) return showToast("Erro de conexão", "danger");
            showToast(res.message, res.success ? "success" : "danger");
            if (res.success) carregarTabela();
        });
    });
}

document.getElementById("formContrato").addEventListener("submit", function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const data = {};
    fd.forEach((v, k) => data[k] = v);
    data.action = data.id ? "update" : "create";
    ajaxPost("" + window.BASE_URL + "/ajax/contratos.php", data, function(err, res) {
        if (err || !res) return showToast("Erro de conexão", "danger");
        showToast(res.message, res.success ? "success" : "danger");
        if (res.success) { bootstrap.Modal.getInstance(document.getElementById("modalContrato")).hide(); carregarTabela(); }
    });
});
</script>';
require_once __DIR__ . '/../includes/footer.php';
