<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$page_title = 'Proprietários';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="fade-in">
    <div class="page-header">
        <h1><i class="bi bi-person-badge-fill me-2"></i>Proprietários</h1>
        <button class="btn btn-primary" id="btnNovoProprietario">
            <i class="bi bi-plus-lg me-1"></i>Novo Proprietário
        </button>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tblProprietarios" class="table table-hover">
                    <thead><tr>
                        <th>#</th><th>Nome</th><th>CPF/CNPJ</th><th>Telefone</th>
                        <th>E-mail</th><th>Imóveis</th><th>Ações</th>
                    </tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Proprietário -->
<div class="modal fade" id="modalProprietario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalProprietarioTitle">Novo Proprietário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formProprietario">
                <div class="modal-body">
                    <input type="hidden" name="id" id="prop_id">
                    <div class="section-title" style="margin-top:0">Dados Pessoais</div>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" name="nome" id="prop_nome" required maxlength="100">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">CPF / CNPJ</label>
                            <input type="text" class="form-control" name="cpf_cnpj" id="prop_cpf_cnpj" maxlength="18">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">RG</label>
                            <input type="text" class="form-control" name="rg" id="prop_rg" maxlength="20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Telefone</label>
                            <input type="text" class="form-control" name="telefone" id="prop_telefone" maxlength="20" oninput="maskPhone(this)">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control" name="email" id="prop_email">
                        </div>
                    </div>
                    <div class="section-title">Endereço</div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">CEP</label>
                            <input type="text" class="form-control" name="cep" id="prop_cep" maxlength="9" placeholder="00000-000"
                                oninput="maskCep(this)" onblur="buscaCep(this, 'prop')">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Logradouro</label>
                            <input type="text" class="form-control" name="logradouro" id="prop_logradouro">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Número</label>
                            <input type="text" class="form-control" name="numero" id="prop_numero">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bairro</label>
                            <input type="text" class="form-control" name="bairro" id="prop_bairro">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cidade</label>
                            <input type="text" class="form-control" name="cidade" id="prop_cidade">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">UF</label>
                            <input type="text" class="form-control" name="estado" id="prop_estado" maxlength="2">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="prop_status">
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>
                    </div>
                    <div class="section-title">Dados Bancários</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Banco</label>
                            <input type="text" class="form-control" name="banco" id="prop_banco">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Agência</label>
                            <input type="text" class="form-control" name="agencia" id="prop_agencia">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Conta</label>
                            <input type="text" class="form-control" name="conta" id="prop_conta">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" name="tipo_conta" id="prop_tipo_conta">
                                <option value="corrente">Corrente</option>
                                <option value="poupanca">Poupança</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">PIX</label>
                            <input type="text" class="form-control" name="pix" id="prop_pix" placeholder="CPF, e-mail, telefone ou chave aleatória">
                        </div>
                    </div>
                    <div class="section-title">Observações</div>
                    <textarea class="form-control" name="observacoes" id="prop_observacoes" rows="2"></textarea>
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
function carregarTabela() {
    if (dt) { dt.destroy(); $("#tblProprietarios tbody").empty(); }
    dt = $("#tblProprietarios").DataTable({
        ...dtDefaults,
        ajax: { url: "" + window.BASE_URL + "/ajax/proprietarios.php?action=list", dataSrc: "data" },
        columns: [
            { data: "id", width: "50px" },
            { data: "nome" },
            { data: "cpf_cnpj", defaultContent: "-" },
            { data: "telefone", defaultContent: "-" },
            { data: "email", defaultContent: "-" },
            { data: "total_imoveis", defaultContent: "0", render: v => `<span class="badge bg-primary">${v||0}</span>` },
            { data: "id", orderable: false, render: function(id, t, row) {
                return `<button class="btn btn-action btn-outline-primary me-1" onclick="editar(${id})"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-action btn-outline-danger" data-nome="${esc(row.nome)}" onclick="excluir(${id}, this.dataset.nome)"><i class="bi bi-trash"></i></button>`;
            }}
        ]
    });
}
carregarTabela();

document.getElementById("btnNovoProprietario").addEventListener("click", function() {
    document.getElementById("modalProprietarioTitle").textContent = "Novo Proprietário";
    document.getElementById("formProprietario").reset();
    document.getElementById("prop_id").value = "";
    new bootstrap.Modal(document.getElementById("modalProprietario")).show();
});

function editar(id) {
    fetch("" + window.BASE_URL + "/ajax/proprietarios.php?action=get&id=" + id)
        .then(r => r.json()).then(res => {
            if (!res.success) return showToast(res.message, "danger");
            const d = res.data;
            document.getElementById("modalProprietarioTitle").textContent = "Editar Proprietário";
            const fields = ["id","nome","cpf_cnpj","rg","telefone","email","status","cep","logradouro","numero","bairro","cidade","estado","banco","agencia","conta","tipo_conta","pix","observacoes"];
            fields.forEach(f => { const el = document.getElementById("prop_" + f); if (el) el.value = d[f] || ""; });
            new bootstrap.Modal(document.getElementById("modalProprietario")).show();
        });
}

function excluir(id, nome) {
    confirmDelete(`Excluir proprietário "${nome}"?`, function() {
        ajaxPost("" + window.BASE_URL + "/ajax/proprietarios.php", { action: "delete", id }, function(err, res) {
            if (err || !res) return showToast("Erro de conexão", "danger");
            showToast(res.message, res.success ? "success" : "danger");
            if (res.success) carregarTabela();
        });
    });
}

document.getElementById("formProprietario").addEventListener("submit", function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const data = {};
    fd.forEach((v, k) => data[k] = v);
    data.action = data.id ? "update" : "create";
    ajaxPost("" + window.BASE_URL + "/ajax/proprietarios.php", data, function(err, res) {
        if (err || !res) return showToast("Erro de conexão", "danger");
        showToast(res.message, res.success ? "success" : "danger");
        if (res.success) { bootstrap.Modal.getInstance(document.getElementById("modalProprietario")).hide(); carregarTabela(); }
    });
});
</script>';
require_once __DIR__ . '/../includes/footer.php';
