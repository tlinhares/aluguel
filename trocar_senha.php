<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$obrigatorio = !empty($_SESSION['trocar_senha']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Trocar senha | AluguelPRO</title>
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="login-page lp is-ready">
<div class="lp-stage">
    <div class="lp-brand" aria-hidden="true">
        <div class="lp-glow"></div>
        <div class="lp-logo"><i class="bi bi-shield-lock-fill"></i></div>
        <div class="lp-name">AluguelPRO</div>
        <div class="lp-tag">Segurança da conta</div>
    </div>

    <section class="lp-sheet" aria-labelledby="lpTitulo">
        <div class="lp-grabber" aria-hidden="true"></div>
        <h1 id="lpTitulo" class="lp-title lp-item" style="--i:0"><?= $obrigatorio ? 'Crie sua nova senha' : 'Trocar senha' ?></h1>
        <p class="lp-sub lp-item" style="--i:1"><?= $obrigatorio
            ? 'Por segurança, defina uma senha pessoal antes de continuar.'
            : 'Use ao menos 8 caracteres.' ?></p>

        <div id="alertMsg" class="lp-alert d-none" role="alert"></div>

        <form id="formSenha" novalidate>
            <div class="lp-field lp-item" style="--i:2">
                <label for="atual">Senha atual</label>
                <div class="lp-input"><i class="bi bi-lock" aria-hidden="true"></i>
                    <input type="password" id="atual" name="atual" required autocomplete="current-password"></div>
            </div>
            <div class="lp-field lp-item" style="--i:3">
                <label for="nova">Nova senha <small>(mínimo 8 caracteres)</small></label>
                <div class="lp-input"><i class="bi bi-key" aria-hidden="true"></i>
                    <input type="password" id="nova" name="nova" required minlength="8" autocomplete="new-password"></div>
            </div>
            <div class="lp-field lp-item" style="--i:4">
                <label for="confirma">Confirmar nova senha</label>
                <div class="lp-input"><i class="bi bi-key-fill" aria-hidden="true"></i>
                    <input type="password" id="confirma" name="confirma" required autocomplete="new-password"></div>
            </div>
            <button type="submit" class="lp-btn lp-item" style="--i:5" id="btnSalvar">
                <span id="btnText">Salvar e continuar <i class="bi bi-arrow-right ms-1"></i></span>
                <span id="btnLoading" class="d-none"><span class="spinner-border spinner-border-sm me-2"></span>Salvando…</span>
            </button>
        </form>
        <div class="lp-foot lp-item" style="--i:6"><a href="<?= BASE_URL ?>/logout.php">Sair</a></div>
    </section>
</div>
<script>
(function () {
    document.getElementById('atual').focus({ preventScroll: true });
    const alerta = document.getElementById('alertMsg');
    const sheet = document.querySelector('.lp-sheet');
    const reduz = matchMedia('(prefers-reduced-motion: reduce)').matches;
    function erro(msg) {
        alerta.textContent = msg; alerta.classList.remove('d-none');
        sheet.classList.remove('lp-shake'); void sheet.offsetWidth; sheet.classList.add('lp-shake');
    }
    function carregando(on) {
        document.getElementById('btnSalvar').disabled = on;
        document.getElementById('btnText').classList.toggle('d-none', on);
        document.getElementById('btnLoading').classList.toggle('d-none', !on);
    }
    document.getElementById('formSenha').addEventListener('submit', function (e) {
        e.preventDefault();
        alerta.classList.add('d-none');
        if (this.nova.value.length < 8) return erro('A nova senha precisa de ao menos 8 caracteres.');
        if (this.nova.value !== this.confirma.value) return erro('A confirmação não confere com a nova senha.');
        carregando(true);
        const fd = new FormData(this); fd.append('action', 'trocar_senha');
        fetch('<?= BASE_URL ?>/ajax/auth.php', { method: 'POST', body: fd,
            headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content } })
        .then(r => r.json())
        .then(res => {
            if (!res.success) { carregando(false); return erro(res.message); }
            document.body.classList.add('is-leaving');
            setTimeout(() => { location.href = res.redirect; }, reduz ? 0 : 480);
        })
        .catch(() => { carregando(false); erro('Sem conexão com o servidor. Tente novamente.'); });
    });
})();
</script>
</body>
</html>
