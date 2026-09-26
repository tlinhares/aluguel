<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$page_title = 'Inquilinos';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="fade-in">
    <div class="page-header">
        <h1><i class="bi bi-people-fill me-2"></i>Inquilinos</h1>
        <button class="btn btn-primary" id="btnNovoInquilino">
            <i class="bi bi-plus-lg me-1"></i>Novo Inquilino
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tblInquilinos" class="table table-hover">
                    <thead><tr>
                        <th>#</th><th>Nome</th><th>CPF</th><th>Telefone</th>
                        <th>E-mail</th><th>Status</th><th>Ações</th>
                    </tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Inquilino -->
<div class="modal fade" id="modalInquilino" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalInquilinoTitle">Novo Inquilino</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formInquilino">
                <div class="modal-body">
                    <input type="hidden" name="id" id="inq_id">
                    <div class="section-title" style="margin-top:0">Dados Pessoais</div>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" name="nome" id="inq_nome" required maxlength="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estado Civil</label>
                            <select class="form-select" name="estado_civil" id="inq_estado_civil">
                                <option value="solteiro">Solteiro(a)</option>
                                <option value="casado">Casado(a)</option>
                                <option value="divorciado">Divorciado(a)</option>
                                <option value="viuvo">Viúvo(a)</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CPF</label>
                            <input type="text" class="form-control" name="cpf" id="inq_cpf" maxlength="14" oninput="maskCpf(this)" placeholder="000.000.000-00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">RG</label>
                            <input type="text" class="form-control" name="rg" id="inq_rg" maxlength="20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Profissão</label>
                            <input type="text" class="form-control" name="profissao" id="inq_profissao" maxlength="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Telefone</label>
                            <input type="text" class="form-control" name="telefone" id="inq_telefone" maxlength="20" oninput="maskPhone(this)" placeholder="(00) 00000-0000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control" name="email" id="inq_email">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Renda Mensal</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:var(--input-bg);border-color:var(--input-border);color:var(--text-secondary)">R$</span>
                                <input type="text" class="form-control" name="renda" id="inq_renda" placeholder="0,00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="inq_status">
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>
                    </div>

                    <div class="section-title">Endereço</div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">CEP</label>
                            <input type="text" class="form-control" name="cep" id="inq_cep" maxlength="9" placeholder="00000-000"
                                oninput="maskCep(this)" onblur="buscaCep(this, 'inq')">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Logradouro</label>
                            <input type="text" class="form-control" name="logradouro" id="inq_logradouro" maxlength="200">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Número</label>
                            <input type="text" class="form-control" name="numero" id="inq_numero" maxlength="20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Complemento</label>
                            <input type="text" class="form-control" name="complemento" id="inq_complemento" maxlength="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bairro</label>
                            <input type="text" class="form-control" name="bairro" id="inq_bairro" maxlength="100">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Cidade</label>
                            <input type="text" class="form-control" name="cidade" id="inq_cidade" maxlength="100">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">UF</label>
                            <input type="text" class="form-control" name="estado" id="inq_estado" maxlength="2" placeholder="AM">
                        </div>
                    </div>

                    <div class="section-title">Observações</div>
                    <textarea class="form-control" name="observacoes" id="inq_observacoes" rows="3"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarInquilino">
                        <i class="bi bi-save me-1"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extra_js = '<script>
let dt;
function carregarTabela() {
    if (dt) { dt.destroy(); $("#tblInquilinos tbody").empty(); }
    dt = $("#tblInquilinos").DataTable({
        ...dtDefaults,
        ajax: {
            url: "" + window.BASE_URL + "/ajax/inquilinos.php?action=list",
            dataSrc: "data"
        },
        columns: [
            { data: "id", width: "50px" },
            { data: "nome" },
            { data: "cpf", defaultContent: "-" },
            { data: "telefone", defaultContent: "-", render: d => d ? d : "-" },
            { data: "email", defaultContent: "-" },
            { data: "status", render: s => s === "ativo" ? \'<span class="badge bg-success">Ativo</span>\' : \'<span class="badge bg-secondary">Inativo</span>\' },
            { data: "id", orderable: false, render: function(id, t, row) {
                return `<button class="btn btn-action btn-outline-primary me-1" onclick="editarInquilino(${id})" title="Editar"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-action btn-outline-danger" onclick="excluirInquilino(${id}, \'${row.nome.replace(/\'/g,"\\\'")}\' )" title="Excluir"><i class="bi bi-trash"></i></button>`;
            }}
        ]
    });
}
carregarTabela();

document.getElementById("btnNovoInquilino").addEventListener("click", function() {
    document.getElementById("modalInquilinoTitle").textContent = "Novo Inquilino";
    document.getElementById("formInquilino").reset();
    document.getElementById("inq_id").value = "";
    new bootstrap.Modal(document.getElementById("modalInquilino")).show();
});

function editarInquilino(id) {
    fetch("" + window.BASE_URL + "/ajax/inquilinos.php?action=get&id=" + id)
        .then(r => r.json()).then(res => {
            if (!res.success) return showToast(res.message, "danger");
            const d = res.data;
            document.getElementById("modalInquilinoTitle").textContent = "Editar Inquilino";
            const fields = ["id","nome","cpf","rg","telefone","email","profissao","estado_civil","renda","status","cep","logradouro","numero","complemento","bairro","cidade","estado","observacoes"];
            fields.forEach(f => {
                const el = document.getElementById("inq_" + f);
                if (el) el.value = d[f] || "";
            });
            new bootstrap.Modal(document.getElementById("modalInquilino")).show();
        });
}

function excluirInquilino(id, nome) {
    confirmDelete(`Excluir inquilino "${nome}"?`, function() {
        ajaxPost("" + window.BASE_URL + "/ajax/inquilinos.php", { action: "delete", id }, function(err, res) {
            if (err || !res) return showToast("Erro de conexão", "danger");
            showToast(res.message, res.success ? "success" : "danger");
            if (res.success) carregarTabela();
        });
    });
}

document.getElementById("formInquilino").addEventListener("submit", function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const data = {};
    fd.forEach((v, k) => data[k] = v);
    data.action = data.id ? "update" : "create";
    ajaxPost("" + window.BASE_URL + "/ajax/inquilinos.php", data, function(err, res) {
        if (err || !res) return showToast("Erro de conexão", "danger");
        showToast(res.message, res.success ? "success" : "danger");
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById("modalInquilino")).hide();
            carregarTabela();
        }
    });
});
</script>';
require_once __DIR__ . '/../includes/footer.php';
