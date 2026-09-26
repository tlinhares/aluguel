<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | AluguelPRO</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
    <meta name="description" content="AluguelPRO - Sistema de Controle de Aluguel. Acesse sua conta.">
</head>
<body class="login-page">
    <div class="login-card fade-in">
        <div class="login-logo">
            <i class="bi bi-building-fill-check"></i>
        </div>
        <h1 class="login-title">AluguelPRO</h1>
        <p class="login-subtitle">Sistema de Controle de Aluguel</p>

        <div id="alertMsg" class="alert alert-danger d-none" role="alert"></div>

        <form id="loginForm">
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <div class="input-group">
                    <span class="input-group-text" style="background:var(--input-bg);border-color:var(--input-border);color:var(--text-secondary)">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="seu@email.com" required autocomplete="email" autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label for="senha" class="form-label">Senha</label>
                <div class="input-group">
                    <span class="input-group-text" style="background:var(--input-bg);border-color:var(--input-border);color:var(--text-secondary)">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" class="form-control" id="senha" name="senha" 
                           placeholder="••••••••" required autocomplete="current-password">
                    <button class="btn btn-outline-secondary" type="button" id="toggleSenha" 
                            style="border-color:var(--input-border);color:var(--text-secondary)">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100" id="btnLogin">
                <span id="btnText"><i class="bi bi-box-arrow-in-right me-2"></i>Entrar</span>
                <span id="btnLoading" class="d-none">
                    <span class="spinner-border spinner-border-sm me-2"></span>Autenticando...
                </span>
            </button>
        </form>

        <div class="text-center mt-4">
            <small class="text-muted">© <?= date('Y') ?> AluguelPRO v1.0</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle senha
        document.getElementById('toggleSenha').addEventListener('click', function () {
            const input = document.getElementById('senha');
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        });

        // Login AJAX
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('btnLogin');
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');
            const alert = document.getElementById('alertMsg');

            btn.disabled = true;
            btnText.classList.add('d-none');
            btnLoading.classList.remove('d-none');
            alert.classList.add('d-none');

            const fd = new FormData(this);
            fd.append('action', 'login');

            fetch('<?= BASE_URL ?>/ajax/auth.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        window.location.href = '<?= BASE_URL ?>/dashboard.php';
                    } else {
                        alert.textContent = res.message;
                        alert.classList.remove('d-none');
                        btn.disabled = false;
                        btnText.classList.remove('d-none');
                        btnLoading.classList.add('d-none');
                    }
                })
                .catch(() => {
                    alert.textContent = 'Erro de conexão. Tente novamente.';
                    alert.classList.remove('d-none');
                    btn.disabled = false;
                    btnText.classList.remove('d-none');
                    btnLoading.classList.add('d-none');
                });
        });
    </script>
</body>
</html>
