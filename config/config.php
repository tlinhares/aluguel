<?php
// ============================================================
// CONFIGURAÇÕES DO SISTEMA
// ============================================================
// Credenciais do banco ficam em config.local.php (fora do git).
// Copie config.local.example.php para config.local.php e ajuste.
// Credenciais: variáveis de ambiente (deploy Coolify) OU config.local.php (dev local).
if (getenv('DB_HOST') !== false) {
    define('DB_HOST', getenv('DB_HOST'));
    define('DB_USER', getenv('DB_USER'));
    define('DB_PASS', getenv('DB_PASS'));
    define('DB_NAME', getenv('DB_NAME'));
} elseif (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
} else {
    die('Configure as variáveis de ambiente DB_* ou copie config/config.local.example.php para config.local.php.');
}

define('SISTEMA_NOME', 'AluguelPRO');
define('SISTEMA_VERSAO', '1.0.0');
define('BASE_URL', getenv('BASE_URL') !== false ? getenv('BASE_URL') : '/aluguel');

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_name('aluguel_sess');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('America/Manaus');

// Error reporting (desabilitar em produção)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
