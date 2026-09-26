<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$page_title = 'Recibos';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="fade-in">
    <div class="page-header">
        <h1><i class="bi bi-receipt me-2"></i>Recibos</h1>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tblRecibos" class="table table-hover">
                    <thead><tr>
                        <th>Número</th><th>Inquilino</th><th>Imóvel</th><th>Competência</th>
                        <th>Valor Total</th><th>Pagamento</th><th>Forma</th><th>Ações</th>
                    </tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $extra_js = '<script>
let dt;
function carregarTabela() {
    if (dt) { dt.destroy(); $("#tblRecibos tbody").empty(); }
    dt = $("#tblRecibos").DataTable({
        ...dtDefaults,
        ajax: { url: "" + window.BASE_URL + "/ajax/recibos.php?action=list", dataSrc: "data" },
        columns: [
            { data: "numero" },
            { data: "inquilino_nome", defaultContent: "-" },
            { data: "imovel_end", defaultContent: "-" },
            { data: "competencia", render: d => { const m=["","Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"]; const p=d.split("-"); return m[parseInt(p[1])]+"/"+p[0]; } },
            { data: "valor_total", render: d => `<span class="text-money">R$ ${parseFloat(d).toLocaleString("pt-BR",{minimumFractionDigits:2})}</span>` },
            { data: "data_pagamento", render: d => d ? d.split("-").reverse().join("/") : "-" },
            { data: "forma_pagamento", render: d => d ? d.charAt(0).toUpperCase()+d.slice(1) : "-" },
            { data: "id", orderable: false, render: id =>
                `<a href="${window.BASE_URL}/pages/recibo_view.php?id=${id}" target="_blank" class="btn btn-action btn-outline-primary me-1" title="Ver Recibo"><i class="bi bi-eye"></i></a>
                 <a href="${window.BASE_URL}/pages/recibo_view.php?id=${id}&print=1" target="_blank" class="btn btn-action btn-outline-secondary" title="Imprimir"><i class="bi bi-printer"></i></a>`
            }
        ]
    });
}
carregarTabela();
</script>';
require_once __DIR__ . '/../includes/footer.php';
