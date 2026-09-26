<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged()) {
    header('Location: ' . BASE_URL . (!empty($_SESSION['trocar_senha']) ? '/trocar_senha.php' : '/dashboard.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <?php include __DIR__ . '/includes/tema.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Entrar | AluguelPRO</title>
    <meta name="description" content="AluguelPRO - Sistema de Controle de Aluguel. Acesse sua conta.">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="login-page lp is-splash">
<div class="lp-tema"><button type="button" class="btn-tema" onclick="alternarTema(this)" aria-label="Alternar tema"><i class="bi bi-moon-stars"></i></button></div>
<div class="lp-stage">
    <!-- Marca: surge no centro (splash) e sobe quando o painel entra -->
    <div class="lp-brand" aria-hidden="true">
        <div class="lp-glow"></div>
        <div class="lp-logo"><i class="bi bi-building-fill-check"></i></div>
        <div class="lp-name">AluguelPRO</div>
        <div class="lp-tag">Controle de aluguéis</div>
    </div>
    <div class="lp-progress" aria-hidden="true"><span></span></div>

    <!-- Painel que desliza de baixo com o formulário -->
    <section class="lp-sheet" aria-labelledby="lpTitulo">
        <div class="lp-grabber" aria-hidden="true"></div>
        <h1 id="lpTitulo" class="lp-title lp-item" style="--i:0">Bem-vindo de volta</h1>
        <p class="lp-sub lp-item" style="--i:1">Entre para gerenciar imóveis, contratos e recebimentos.</p>

        <div id="alertMsg" class="lp-alert d-none" role="alert"></div>

        <form id="loginForm" novalidate>
            <div class="lp-field lp-item" style="--i:2">
                <label for="email">E-mail</label>
                <div class="lp-input">
                    <i class="bi bi-envelope" aria-hidden="true"></i>
                    <input type="email" id="email" name="email" placeholder="seu@email.com" required autocomplete="email">
                </div>
            </div>
            <div class="lp-field lp-item" style="--i:3">
                <label for="senha">Senha</label>
                <div class="lp-input">
                    <i class="bi bi-lock" aria-hidden="true"></i>
                    <input type="password" id="senha" name="senha" placeholder="Sua senha" required autocomplete="current-password">
                    <button type="button" class="lp-eye" id="toggleSenha" aria-label="Mostrar senha"><i class="bi bi-eye"></i></button>
                </div>
            </div>
            <button type="submit" class="lp-btn lp-item" style="--i:4" id="btnLogin">
                <span id="btnText">Entrar <i class="bi bi-arrow-right ms-1"></i></span>
                <span id="btnLoading" class="d-none"><span class="spinner-border spinner-border-sm me-2"></span>Entrando…</span>
            </button>
        </form>

        <div class="lp-foot lp-item" style="--i:5">© <?= date('Y') ?> AluguelPRO</div>
    </section>
</div>

<script>
(function () {
    const body = document.body;
    const reduz = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const jaViuSplash = sessionStorage.getItem('lp_splash') === '1';

    // 1) splash → 2) marca sobe e o painel desliza (pula o splash em recargas e p/ quem reduz movimento)
    function pronto() {
        body.classList.remove('is-splash');
        body.classList.add('is-ready');
        setTimeout(() => document.getElementById('email').focus({ preventScroll: true }), reduz ? 0 : 650);
    }
    if (reduz || jaViuSplash) pronto();
    else { sessionStorage.setItem('lp_splash', '1'); setTimeout(pronto, 1500); }

    document.getElementById('toggleSenha').addEventListener('click', function () {
        const input = document.getElementById('senha');
        const mostrar = input.type === 'password';
        input.type = mostrar ? 'text' : 'password';
        this.querySelector('i').className = mostrar ? 'bi bi-eye-slash' : 'bi bi-eye';
        this.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
    });

    const alerta = document.getElementById('alertMsg');
    const sheet = document.querySelector('.lp-sheet');
    function erro(msg) {
        alerta.textContent = msg;
        alerta.classList.remove('d-none');
        sheet.classList.remove('lp-shake'); void sheet.offsetWidth; sheet.classList.add('lp-shake');
    }
    function carregando(on) {
        document.getElementById('btnLogin').disabled = on;
        document.getElementById('btnText').classList.toggle('d-none', on);
        document.getElementById('btnLoading').classList.toggle('d-none', !on);
    }

    document.getElementById('loginForm').addEventListener('submit', function (e) {
        e.preventDefault();
        alerta.classList.add('d-none');
        if (!this.email.value.trim() || !this.senha.value) return erro('Informe e-mail e senha.');
        carregando(true);
        const fd = new FormData(this);
        fd.append('action', 'login');
        fetch('<?= BASE_URL ?>/ajax/auth.php', {
            method: 'POST', body: fd,
            headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success) { carregando(false); return erro(res.message || 'Não foi possível entrar.'); }
            // 3) sucesso: o painel se dissolve e a próxima tela assume
            body.classList.add('is-leaving');
            setTimeout(() => { window.location.href = res.redirect || '<?= BASE_URL ?>/dashboard.php'; }, reduz ? 0 : 480);
        })
        .catch(() => { carregando(false); erro('Sem conexão com o servidor. Tente novamente.'); });
    });
})();
</script>
</body>
</html>
