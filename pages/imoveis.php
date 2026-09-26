<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$page_title = 'Imóveis';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="fade-in">
    <div class="page-header">
        <h1><i class="bi bi-house-fill me-2"></i>Imóveis</h1>
        <button class="btn btn-primary" id="btnNovoImovel"><i class="bi bi-plus-lg me-1"></i>Novo Imóvel</button>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tblImoveis" class="table table-hover">
                    <thead><tr>
                        <th>#</th><th>Endereço</th><th>Tipo</th><th>Proprietário</th>
                        <th>Valor Aluguel</th><th>Status</th><th>Ações</th>
                    </tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Imóvel -->
<div class="modal fade" id="modalImovel" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalImovelTitle">Novo Imóvel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formImovel">
                <div class="modal-body">
                    <input type="hidden" name="id" id="imo_id">
                    <div class="section-title" style="margin-top:0">Dados do Imóvel</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Proprietário *</label>
                            <select class="form-select" name="proprietario_id" id="imo_proprietario_id" required></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" name="tipo" id="imo_tipo">
                                <option value="casa">Casa</option><option value="apartamento">Apartamento</option>
                                <option value="comercial">Comercial</option><option value="terreno">Terreno</option>
                                <option value="sala">Sala</option><option value="outro">Outro</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="imo_status">
                                <option value="disponivel">Disponível</option><option value="alugado">Alugado</option>
                                <option value="manutencao">Manutenção</option><option value="inativo">Inativo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descrição</label>
                            <input type="text" class="form-control" name="descricao" id="imo_descricao" maxlength="200">
                        </div>
                    </div>
                    <div class="section-title">Endereço</div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">CEP</label>
                            <input type="text" class="form-control" name="cep" id="imo_cep" maxlength="9"
                                oninput="maskCep(this)" onblur="buscaCep(this, 'imo')">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Logradouro *</label>
                            <input type="text" class="form-control" name="logradouro" id="imo_logradouro" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Número</label>
                            <input type="text" class="form-control" name="numero" id="imo_numero">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Complemento</label>
                            <input type="text" class="form-control" name="complemento" id="imo_complemento">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bairro</label>
                            <input type="text" class="form-control" name="bairro" id="imo_bairro">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cidade</label>
                            <input type="text" class="form-control" name="cidade" id="imo_cidade">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">UF</label>
                            <input type="text" class="form-control" name="estado" id="imo_estado" maxlength="2">
                        </div>
                    </div>
                    <div class="section-title">Características</div>
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Área (m²)</label>
                            <input type="number" class="form-control" name="area" id="imo_area" min="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Quartos</label>
                            <input type="number" class="form-control" name="quartos" id="imo_quartos" min="0" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Banheiros</label>
                            <input type="number" class="form-control" name="banheiros" id="imo_banheiros" min="0" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Vagas</label>
                            <input type="number" class="form-control" name="vagas" id="imo_vagas" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Valor Aluguel *</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:var(--input-bg);border-color:var(--input-border);color:var(--text-secondary)">R$</span>
                                <input type="number" class="form-control" name="valor_aluguel" id="imo_valor_aluguel" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Condomínio</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:var(--input-bg);border-color:var(--input-border);color:var(--text-secondary)">R$</span>
                                <input type="number" class="form-control" name="valor_condominio" id="imo_valor_condominio" min="0" step="0.01" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">IPTU (mensal)</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:var(--input-bg);border-color:var(--input-border);color:var(--text-secondary)">R$</span>
                                <input type="number" class="form-control" name="valor_iptu" id="imo_valor_iptu" min="0" step="0.01" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="section-title">Observações</div>
                    <textarea class="form-control" name="observacoes" id="imo_observacoes" rows="2"></textarea>
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
function carregarProprietarios() {
    fetch("" + window.BASE_URL + "/ajax/proprietarios.php?action=list")
        .then(r => r.json()).then(res => {
            const sel = document.getElementById("imo_proprietario_id");
            sel.innerHTML = \'<option value="">Selecione...</option>\';
            (res.data || []).forEach(p => {
                sel.innerHTML += `<option value="${p.id}">${p.nome}</option>`;
            });
        });
}
carregarProprietarios();

const statusBadge = { disponivel:\'<span class="badge bg-success">Disponível</span>\', alugado:\'<span class="badge bg-primary">Alugado</span>\', manutencao:\'<span class="badge bg-warning text-dark">Manutenção</span>\', inativo:\'<span class="badge bg-secondary">Inativo</span>\' };

function carregarTabela() {
    if (dt) { dt.destroy(); $("#tblImoveis tbody").empty(); }
    dt = $("#tblImoveis").DataTable({
        ...dtDefaults,
        ajax: { url: "" + window.BASE_URL + "/ajax/imoveis.php?action=list", dataSrc: "data" },
        columns: [
            { data: "id", width: "50px" },
            { data: "logradouro", render: (d, t, row) => `${d}, ${row.numero||""} ${row.bairro ? "- "+row.bairro : ""}<br><small class="text-muted">${row.cidade||""} ${row.estado ? "/"+row.estado : ""}</small>` },
            { data: "tipo", render: d => d ? d.charAt(0).toUpperCase()+d.slice(1) : "-" },
            { data: "proprietario_nome", defaultContent: "-" },
            { data: "valor_aluguel", render: d => `<span class="text-money">R$ ${parseFloat(d).toLocaleString("pt-BR",{minimumFractionDigits:2})}</span>` },
            { data: "status", render: d => statusBadge[d] || d },
            { data: "id", orderable: false, render: function(id, t, row) {
                return `<button class="btn btn-action btn-outline-primary me-1" onclick="editar(${id})"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-action btn-outline-danger" onclick="excluir(${id})"><i class="bi bi-trash"></i></button>`;
            }}
        ]
    });
}
carregarTabela();

document.getElementById("btnNovoImovel").addEventListener("click", function() {
    document.getElementById("modalImovelTitle").textContent = "Novo Imóvel";
    document.getElementById("formImovel").reset();
    document.getElementById("imo_id").value = "";
    new bootstrap.Modal(document.getElementById("modalImovel")).show();
});

function editar(id) {
    fetch("" + window.BASE_URL + "/ajax/imoveis.php?action=get&id=" + id)
        .then(r => r.json()).then(res => {
            if (!res.success) return showToast(res.message, "danger");
            const d = res.data;
            document.getElementById("modalImovelTitle").textContent = "Editar Imóvel";
            const fields = ["id","proprietario_id","tipo","status","descricao","cep","logradouro","numero","complemento","bairro","cidade","estado","area","quartos","banheiros","vagas","valor_aluguel","valor_condominio","valor_iptu","observacoes"];
            fields.forEach(f => { const el = document.getElementById("imo_" + f); if (el) el.value = d[f] || ""; });
            new bootstrap.Modal(document.getElementById("modalImovel")).show();
        });
}

function excluir(id) {
    confirmDelete("Deseja excluir este imóvel?", function() {
        ajaxPost("" + window.BASE_URL + "/ajax/imoveis.php", { action: "delete", id }, function(err, res) {
            if (err || !res) return showToast("Erro de conexão", "danger");
            showToast(res.message, res.success ? "success" : "danger");
            if (res.success) carregarTabela();
        });
    });
}

document.getElementById("formImovel").addEventListener("submit", function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const data = {};
    fd.forEach((v, k) => data[k] = v);
    data.action = data.id ? "update" : "create";
    ajaxPost("" + window.BASE_URL + "/ajax/imoveis.php", data, function(err, res) {
        if (err || !res) return showToast("Erro de conexão", "danger");
        showToast(res.message, res.success ? "success" : "danger");
        if (res.success) { bootstrap.Modal.getInstance(document.getElementById("modalImovel")).hide(); carregarTabela(); }
    });
});
</script>';
require_once __DIR__ . '/../includes/footer.php';
