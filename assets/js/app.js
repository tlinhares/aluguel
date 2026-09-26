// ============================================================
// ALUGUEL PRO - APP JS
// ============================================================

// ---- Segurança: escape de HTML (use em TODO dado vindo do banco) ----
function esc(s) {
    return String(s ?? "").replace(/[&<>"']/g, c => ({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]));
}

// ---- Segurança: token CSRF anexado a toda requisição POST do sistema ----
(function () {
    const meta = document.querySelector('meta[name="csrf-token"]');
    const token = () => window.CSRF || (meta ? meta.content : '');
    const nativo = window.fetch.bind(window);
    window.fetch = function (url, opts = {}) {
        const metodo = (opts.method || 'GET').toUpperCase();
        const mesmoDominio = typeof url === 'string' && (url.startsWith('/') || url.startsWith(location.origin) || !/^https?:/i.test(url));
        if (metodo !== 'GET' && mesmoDominio) {
            opts.headers = new Headers(opts.headers || {});
            opts.headers.set('X-CSRF-Token', token());
        }
        return nativo(url, opts);
    };
    if (window.jQuery) {
        jQuery.ajaxSetup({ beforeSend: (xhr, s) => { if (s.type !== 'GET') xhr.setRequestHeader('X-CSRF-Token', token()); } });
    }
})();

// ---- Sidebar Toggle ----
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleMain = document.getElementById('sidebarToggleMain');
    const toggleSidebar = document.getElementById('sidebarToggle');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('show');
        if (overlay) overlay.classList.add('show');
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('show');
        if (overlay) overlay.classList.remove('show');
    }

    if (toggleMain) toggleMain.addEventListener('click', () => {
        if (sidebar && sidebar.classList.contains('show')) closeSidebar();
        else openSidebar();
    });

    if (toggleSidebar) toggleSidebar.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);
});

// ---- Toast Notifications ----
function showToast(message, type = 'success') {
    const icons = {
        success: 'bi-check-circle-fill text-success',
        danger: 'bi-x-circle-fill text-danger',
        warning: 'bi-exclamation-triangle-fill text-warning',
        info: 'bi-info-circle-fill text-info'
    };
    const icon = icons[type] || icons.info;
    const id = 'toast_' + Date.now();
    const html = `
        <div id="${id}" class="toast toast-${type} align-items-center show" role="alert" aria-atomic="true">
            <div class="toast-header">
                <i class="bi ${icon} me-2"></i>
                <strong class="me-auto">${type === 'success' ? 'Sucesso' : type === 'danger' ? 'Erro' : 'Aviso'}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body"></div>
        </div>`;
    const container = document.getElementById('toastContainer');
    if (container) {
        container.insertAdjacentHTML('beforeend', html);
        const el = document.getElementById(id);
        el.querySelector('.toast-body').textContent = message;
        const bsToast = new bootstrap.Toast(el, { delay: 4500 });
        bsToast.show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    }
}

// ---- AJAX Helper ----
function ajaxPost(url, data, callback) {
    const formData = new FormData();
    for (const key in data) {
        formData.append(key, data[key] !== null && data[key] !== undefined ? data[key] : '');
    }
    fetch(url, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => callback(null, res))
        .catch(err => callback(err, null));
}

function ajaxGet(url, callback) {
    fetch(url)
        .then(r => r.json())
        .then(res => callback(null, res))
        .catch(err => callback(err, null));
}

// ---- DataTable Default Options ----
const dtDefaults = {
    language: {
        decimal: ',',
        thousands: '.',
        emptyTable: 'Nenhum registro encontrado',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
        infoEmpty: 'Mostrando 0 a 0 de 0 registros',
        infoFiltered: '(filtrado de _MAX_ registros)',
        lengthMenu: 'Mostrar _MENU_ registros',
        loadingRecords: 'Carregando...',
        processing: 'Processando...',
        search: 'Buscar:',
        zeroRecords: 'Nenhum registro encontrado',
        paginate: { first: '«', previous: '‹', next: '›', last: '»' }
    },
    pageLength: 15,
    responsive: true,
    columnDefs: [{ targets: '_all', render: (d, type) => (type === 'display' && typeof d === 'string') ? esc(d) : d }],
    order: [[0, 'desc']]
};

// ---- Form Reset & Modal Open ----
function openModal(modalId, title, formId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    if (title) {
        const titleEl = modal.querySelector('.modal-title');
        if (titleEl) titleEl.textContent = title;
    }
    if (formId) {
        const form = document.getElementById(formId);
        if (form) form.reset();
    }
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    return bsModal;
}

// ---- Confirm Delete ----
function confirmDelete(message, callback) {
    let el = document.getElementById('modalConfirmar');
    if (!el) {
        document.body.insertAdjacentHTML('beforeend', `
        <div class="modal fade" id="modalConfirmar" tabindex="-1" aria-labelledby="confirmarTexto" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content">
            <div class="modal-body text-center p-4">
              <div class="confirm-icon"><i class="bi bi-exclamation-triangle"></i></div>
              <p id="confirmarTexto" class="mb-0 mt-3"></p>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0 pb-4">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="button" class="btn btn-danger" id="btnConfirmarAcao">Confirmar</button>
            </div>
          </div></div>
        </div>`);
        el = document.getElementById('modalConfirmar');
    }
    el.querySelector('#confirmarTexto').textContent = message || 'Deseja realmente excluir este registro?';
    const modal = bootstrap.Modal.getOrCreateInstance(el);
    const btn = el.querySelector('#btnConfirmarAcao');
    const novo = btn.cloneNode(true);           // limpa listeners anteriores
    btn.replaceWith(novo);
    novo.addEventListener('click', () => { modal.hide(); callback(); });
    modal.show();
}

// ---- Format Currency Input ----
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-money]').forEach(function (el) {
        el.addEventListener('input', function () {
            let val = this.value.replace(/\D/g, '');
            val = (parseFloat(val) / 100).toFixed(2);
            this.value = isNaN(val) ? '0,00' : val.replace('.', ',');
        });
    });
});

// ---- Mask: CPF ----
function maskCpf(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 11);
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    input.value = v;
}

// ---- Mask: Phone ----
function maskPhone(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 11);
    if (v.length <= 10) v = v.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    else v = v.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    input.value = v;
}

// ---- Mask: CEP ----
function maskCep(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 8);
    v = v.replace(/(\d{5})(\d)/, '$1-$2');
    input.value = v;
}

// ---- Busca CEP via ViaCEP ----
function buscaCep(cepInput, prefix) {
    const cep = cepInput.value.replace(/\D/g, '');
    if (cep.length !== 8) return;
    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(r => r.json())
        .then(data => {
            if (data.erro) return;
            const set = (id, val) => {
                const el = document.getElementById(prefix + '_' + id) || document.getElementById(id);
                if (el) el.value = val || '';
            };
            set('logradouro', data.logradouro);
            set('bairro', data.bairro);
            set('cidade', data.localidade);
            set('estado', data.uf);
        })
        .catch(() => {});
}

// ---- Number formatting ----
function formatMoney(val) {
    return 'R$ ' + parseFloat(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
