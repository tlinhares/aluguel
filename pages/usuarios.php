<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
if (!is_admin()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}
$page_title = 'Usuários';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="fade-in">
    <div class="page-header">
        <h1><i class="bi bi-people me-2"></i>Usuários do Sistema</h1>
        <button class="btn btn-primary" id="btnNovoUsuario"><i class="bi bi-plus-lg me-1"></i>Novo Usuário</button>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tblUsuarios" class="table table-hover">
                    <thead><tr>
                        <th>#</th><th>Nome</th><th>E-mail</th><th>Nível</th><th>Status</th><th>Criado em</th><th>Ações</th>
                    </tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Usuário -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUsuarioTitle">Novo Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formUsuario">
                <div class="modal-body">
                    <input type="hidden" name="id" id="usr_id">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" name="nome" id="usr_nome" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">E-mail *</label>
                            <input type="email" class="form-control" name="email" id="usr_email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Senha <span id="senha_hint" class="text-muted">(deixe em branco para manter)</span></label>
                            <input type="password" class="form-control" name="senha" id="usr_senha" placeholder="••••••••">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Nível</label>
                            <select class="form-select" name="nivel" id="usr_nivel">
                                <option value="operador">Operador</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="usr_status">
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
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

<?php
$uid = (int)$_SESSION['usuario_id'];
$extra_js = '<script>
let dt;
function carregarTabela() {
    if (dt) { dt.destroy(); $("#tblUsuarios tbody").empty(); }
    dt = $("#tblUsuarios").DataTable({
        ...dtDefaults,
        ajax: { url: "" + window.BASE_URL + "/ajax/usuarios.php?action=list", dataSrc: "data" },
        columns: [
            { data: "id", width: "50px" },
            { data: "nome" },
            { data: "email" },
            { data: "nivel", render: d => d === "admin" ? \'<span class="badge bg-primary">Admin</span>\' : \'<span class="badge bg-secondary">Operador</span>\' },
            { data: "status", render: d => d === "ativo" ? \'<span class="badge bg-success">Ativo</span>\' : \'<span class="badge bg-secondary">Inativo</span>\' },
            { data: "criado_em", render: d => d ? d.split(" ")[0].split("-").reverse().join("/") : "-" },
            { data: "id", orderable: false, render: (id, t, row) =>
                `<button class="btn btn-action btn-outline-primary me-1" onclick="editar(${id})"><i class="bi bi-pencil"></i></button>
                 <button class="btn btn-action btn-outline-danger" onclick="excluir(${id}, \'${row.nome.replace(/\'/g,"\\\'")}\')"><i class="bi bi-trash"></i></button>`
            }
        ]
    });
}
carregarTabela();

document.getElementById("btnNovoUsuario").addEventListener("click", function() {
    document.getElementById("modalUsuarioTitle").textContent = "Novo Usuário";
    document.getElementById("formUsuario").reset();
    document.getElementById("usr_id").value = "";
    document.getElementById("senha_hint").style.display = "none";
    document.getElementById("usr_senha").required = true;
    new bootstrap.Modal(document.getElementById("modalUsuario")).show();
});

function editar(id) {
    fetch("" + window.BASE_URL + "/ajax/usuarios.php?action=get&id=" + id)
        .then(r => r.json()).then(res => {
            if (!res.success) return showToast(res.message, "danger");
            const d = res.data;
            document.getElementById("modalUsuarioTitle").textContent = "Editar Usuário";
            ["id","nome","email","nivel","status"].forEach(f => {
                const el = document.getElementById("usr_" + f);
                if (el) el.value = d[f] || "";
            });
            document.getElementById("usr_senha").value = "";
            document.getElementById("usr_senha").required = false;
            document.getElementById("senha_hint").style.display = "";
            new bootstrap.Modal(document.getElementById("modalUsuario")).show();
        });
}

function excluir(id, nome) {
    if (id == ' . $uid . ') return showToast("Não é possível excluir o usuário logado!", "danger");
    confirmDelete(`Excluir usuário "${nome}"?`, function() {
        ajaxPost("" + window.BASE_URL + "/ajax/usuarios.php", { action: "delete", id }, function(err, res) {
            if (err || !res) return showToast("Erro de conexão", "danger");
            showToast(res.message, res.success ? "success" : "danger");
            if (res.success) carregarTabela();
        });
    });
}

document.getElementById("formUsuario").addEventListener("submit", function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const data = {};
    fd.forEach((v, k) => data[k] = v);
    data.action = data.id ? "update" : "create";
    ajaxPost("" + window.BASE_URL + "/ajax/usuarios.php", data, function(err, res) {
        if (err || !res) return showToast("Erro de conexão", "danger");
        showToast(res.message, res.success ? "success" : "danger");
        if (res.success) { bootstrap.Modal.getInstance(document.getElementById("modalUsuario")).hide(); carregarTabela(); }
    });
});
</script>';
require_once __DIR__ . '/../includes/footer.php';
