<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$page_title = 'Manutenções';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="fade-in">
    <div class="page-header">
        <h1><i class="bi bi-tools me-2"></i>Manutenções</h1>
        <button class="btn btn-primary" id="btnNovaManutencao"><i class="bi bi-plus-lg me-1"></i>Nova Manutenção</button>
    </div>

    <!-- Filtros de status -->
    <div class="filtros-status mb-3" role="group" aria-label="Filtrar por status">
        <button class="btn btn-sm btn-outline-secondary" data-status="" onclick="filtrar('')">Todas</button>
        <button class="btn btn-sm btn-outline-danger" data-status="aberta" onclick="filtrar('aberta')">Abertas</button>
        <button class="btn btn-sm btn-outline-warning" data-status="em_andamento" onclick="filtrar('em_andamento')">Em Andamento</button>
        <button class="btn btn-sm btn-outline-success" data-status="concluida" onclick="filtrar('concluida')">Concluídas</button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tblManutencoes" class="table table-hover">
                    <thead><tr>
                        <th>#</th><th>Título</th><th>Imóvel</th><th>Tipo</th>
                        <th>Prioridade</th><th>Custo</th><th>Status</th><th>Ações</th>
                    </tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Manutenção -->
<div class="modal fade" id="modalManutencao" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalManutencaoTitle">Nova Manutenção</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formManutencao">
                <div class="modal-body">
                    <input type="hidden" name="id" id="mnt_id">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Título *</label>
                            <input type="text" class="form-control" name="titulo" id="mnt_titulo" required maxlength="200">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Imóvel *</label>
                            <select class="form-select" name="imovel_id" id="mnt_imovel_id" required></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" name="tipo" id="mnt_tipo">
                                <option value="eletrica">Elétrica</option><option value="hidraulica">Hidráulica</option>
                                <option value="estrutural">Estrutural</option><option value="pintura">Pintura</option>
                                <option value="jardinagem">Jardinagem</option><option value="limpeza">Limpeza</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Prioridade</label>
                            <select class="form-select" name="prioridade" id="mnt_prioridade">
                                <option value="baixa">Baixa</option><option value="media" selected>Média</option>
                                <option value="alta">Alta</option><option value="urgente">Urgente</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descrição</label>
                            <textarea class="form-control" name="descricao" id="mnt_descricao" rows="3"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Responsável</label>
                            <input type="text" class="form-control" name="responsavel" id="mnt_responsavel" maxlength="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Custo (R$)</label>
                            <input type="number" class="form-control" name="custo" id="mnt_custo" min="0" step="0.01" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="mnt_status">
                                <option value="aberta">Aberta</option><option value="em_andamento">Em Andamento</option>
                                <option value="concluida">Concluída</option><option value="cancelada">Cancelada</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data Abertura *</label>
                            <input type="date" class="form-control" name="data_abertura" id="mnt_data_abertura" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data Prevista</label>
                            <input type="date" class="form-control" name="data_prevista" id="mnt_data_prevista">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data Conclusão</label>
                            <input type="date" class="form-control" name="data_conclusao" id="mnt_data_conclusao">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" name="observacoes" id="mnt_observacoes" rows="2"></textarea>
                        </div>
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
let dt, filtroAtual = "aberta";
const statusB = { aberta:\'<span class="badge bg-danger">Aberta</span>\', em_andamento:\'<span class="badge bg-warning text-dark">Em Andamento</span>\', concluida:\'<span class="badge bg-success">Concluída</span>\', cancelada:\'<span class="badge bg-secondary">Cancelada</span>\' };
const priorB = { baixa:\'<span class="badge bg-secondary">Baixa</span>\', media:\'<span class="badge bg-info">Média</span>\', alta:\'<span class="badge bg-warning text-dark">Alta</span>\', urgente:\'<span class="badge bg-danger">Urgente</span>\' };

function carregarImoveis() {
    fetch("" + window.BASE_URL + "/ajax/imoveis.php?action=list").then(r=>r.json()).then(res=>{
        const sel = document.getElementById("mnt_imovel_id");
        sel.innerHTML = \'<option value="">Selecione...</option>\';
        (res.data||[]).forEach(i => sel.innerHTML += `<option value="${i.id}">${esc(i.logradouro)}, ${esc(i.numero||"")}</option>`);
    });
}
carregarImoveis();

function marcarFiltro() { document.querySelectorAll(".filtros-status [data-status]").forEach(b => { const on = b.dataset.status === filtroAtual; b.classList.toggle("active", on); b.setAttribute("aria-pressed", on); }); }
function filtrar(status) { filtroAtual = status; marcarFiltro(); carregarTabela(); }
marcarFiltro();

function carregarTabela() {
    let url = "" + window.BASE_URL + "/ajax/manutencoes.php?action=list";
    if (filtroAtual) url += "&status=" + filtroAtual;
    if (dt) { dt.destroy(); $("#tblManutencoes tbody").empty(); }
    dt = $("#tblManutencoes").DataTable({
        ...dtDefaults,
        ajax: { url, dataSrc: "data" },
        columns: [
            { data: "id", width: "50px" },
            { data: "titulo" },
            { data: "imovel_end", defaultContent: "-" },
            { data: "tipo", render: d => d ? d.charAt(0).toUpperCase()+d.slice(1) : "-" },
            { data: "prioridade", render: d => priorB[d] || d },
            { data: "custo", render: d => `<span class="text-money">R$ ${parseFloat(d||0).toLocaleString("pt-BR",{minimumFractionDigits:2})}</span>` },
            { data: "status", render: d => statusB[d] || d },
            { data: "id", orderable: false, render: (id, t, row) =>
                `<button class="btn btn-action btn-outline-primary me-1" onclick="editar(${id})"><i class="bi bi-pencil"></i></button>
                 <button class="btn btn-action btn-outline-danger" onclick="excluir(${id})"><i class="bi bi-trash"></i></button>`
            }
        ]
    });
}
carregarTabela();

document.getElementById("btnNovaManutencao").addEventListener("click", function() {
    document.getElementById("modalManutencaoTitle").textContent = "Nova Manutenção";
    document.getElementById("formManutencao").reset();
    document.getElementById("mnt_id").value = "";
    document.getElementById("mnt_data_abertura").value = new Date().toISOString().split("T")[0];
    new bootstrap.Modal(document.getElementById("modalManutencao")).show();
});

function editar(id) {
    fetch("" + window.BASE_URL + "/ajax/manutencoes.php?action=get&id=" + id)
        .then(r => r.json()).then(res => {
            if (!res.success) return showToast(res.message, "danger");
            const d = res.data;
            document.getElementById("modalManutencaoTitle").textContent = "Editar Manutenção";
            const fields = ["id","titulo","imovel_id","tipo","prioridade","descricao","responsavel","custo","status","data_abertura","data_prevista","data_conclusao","observacoes"];
            fields.forEach(f => { const el = document.getElementById("mnt_" + f); if (el) el.value = d[f] || ""; });
            new bootstrap.Modal(document.getElementById("modalManutencao")).show();
        });
}

function excluir(id) {
    confirmDelete("Deseja excluir esta manutenção?", function() {
        ajaxPost("" + window.BASE_URL + "/ajax/manutencoes.php", { action: "delete", id }, function(err, res) {
            if (err || !res) return showToast("Erro de conexão", "danger");
            showToast(res.message, res.success ? "success" : "danger");
            if (res.success) carregarTabela();
        });
    });
}

document.getElementById("formManutencao").addEventListener("submit", function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const data = {};
    fd.forEach((v, k) => data[k] = v);
    data.action = data.id ? "update" : "create";
    ajaxPost("" + window.BASE_URL + "/ajax/manutencoes.php", data, function(err, res) {
        if (err || !res) return showToast("Erro de conexão", "danger");
        showToast(res.message, res.success ? "success" : "danger");
        if (res.success) { bootstrap.Modal.getInstance(document.getElementById("modalManutencao")).hide(); carregarTabela(); }
    });
});
</script>';
require_once __DIR__ . '/../includes/footer.php';
