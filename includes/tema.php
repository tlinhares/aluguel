<script>
/* Tema claro/escuro: aplicado antes da pintura para não piscar. Preferência salva em localStorage "tema"; sem escolha, segue o sistema. */
(function () {
    var t = null;
    try { t = localStorage.getItem('tema'); } catch (e) {}
    if (t !== 'light' && t !== 'dark') t = matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
    document.documentElement.setAttribute('data-bs-theme', t);
    window.alternarTema = function (btn) {
        var novo = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        var html = document.documentElement;
        html.classList.add('trocando-tema');
        html.setAttribute('data-bs-theme', novo);
        try { localStorage.setItem('tema', novo); } catch (e) {}
        document.querySelectorAll('.btn-tema').forEach(function (b) {
            b.classList.add('girando'); setTimeout(function () { b.classList.remove('girando'); }, 400);
            b.querySelector('i').className = novo === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
            b.setAttribute('aria-label', novo === 'dark' ? 'Ativar modo claro' : 'Ativar modo escuro');
            b.title = b.getAttribute('aria-label');
        });
        setTimeout(function () { html.classList.remove('trocando-tema'); }, 400);
        document.dispatchEvent(new CustomEvent('temachange', { detail: novo }));
    };
    document.addEventListener('DOMContentLoaded', function () {
        var escuro = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        document.querySelectorAll('.btn-tema').forEach(function (b) {
            b.querySelector('i').className = escuro ? 'bi bi-sun' : 'bi bi-moon-stars';
            b.setAttribute('aria-label', escuro ? 'Ativar modo claro' : 'Ativar modo escuro');
            b.title = b.getAttribute('aria-label');
        });
    });
})();
</script>
