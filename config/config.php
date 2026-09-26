<?php
// ============================================================
// CONFIGURAÇÕES DO SISTEMA
// ============================================================
// Credenciais do banco ficam em config.local.php (fora do git).
// Copie config.local.example.php para config.local.php e ajuste.
if (!file_exists(__DIR__ . '/config.local.php')) {
    die('Arquivo config/config.local.php não encontrado. Copie config/config.local.example.php e ajuste as credenciais.');
}
require_once __DIR__ . '/config.local.php';

define('SISTEMA_NOME', 'AluguelPRO');
define('SISTEMA_VERSAO', '1.0.0');
define('BASE_URL', '/aluguel');

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
