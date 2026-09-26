<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$action = $_POST['action'] ?? '';
$conn = db_connect();
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ip = trim(explode(',', $ip)[0]);

if ($action === 'login') {
    $email = trim((string)($_POST['email'] ?? ''));
    $senha = (string)($_POST['senha'] ?? '');

    // Limite de tentativas: 5 falhas por e-mail ou 20 por IP em 15 minutos.
    $st = mysqli_prepare($conn, "SELECT
        SUM(email = ?) AS por_email, COUNT(*) AS por_ip
        FROM login_tentativas WHERE (email = ? OR ip = ?) AND tentado_em > (NOW() - INTERVAL 15 MINUTE)");
    mysqli_stmt_bind_param($st, 'sss', $email, $email, $ip);
    mysqli_stmt_execute($st);
    $lim = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    if ((int)$lim['por_email'] >= 5 || (int)$lim['por_ip'] >= 20) {
        json_response(false, 'Muitas tentativas. Aguarde 15 minutos e tente novamente.');
    }

    // Busca só pelo e-mail; a senha é verificada em PHP (nunca no SQL).
    $st = mysqli_prepare($conn, "SELECT id, nome, email, senha, nivel, trocar_senha FROM usuarios WHERE email = ? AND status = 'ativo' LIMIT 1");
    mysqli_stmt_bind_param($st, 's', $email);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));

    $ok = false;
    if ($row) {
        $hash = (string)$row['senha'];
        if (preg_match('/^[a-f0-9]{32}$/', $hash)) {
            // Legado MD5: confere e regrava com password_hash (migração transparente).
            if (hash_equals($hash, md5($senha))) {
                $ok = true;
                $novo = password_hash($senha, PASSWORD_DEFAULT);
                $up = mysqli_prepare($conn, "UPDATE usuarios SET senha = ? WHERE id = ?");
                mysqli_stmt_bind_param($up, 'si', $novo, $row['id']);
                mysqli_stmt_execute($up);
            }
        } elseif (password_verify($senha, $hash)) {
            $ok = true;
            if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                $novo = password_hash($senha, PASSWORD_DEFAULT);
                $up = mysqli_prepare($conn, "UPDATE usuarios SET senha = ? WHERE id = ?");
                mysqli_stmt_bind_param($up, 'si', $novo, $row['id']);
                mysqli_stmt_execute($up);
            }
        }
    }

    if (!$ok) {
        usleep(400000);
        $st = mysqli_prepare($conn, "INSERT INTO login_tentativas (email, ip) VALUES (?, ?)");
        mysqli_stmt_bind_param($st, 'ss', $email, $ip);
        mysqli_stmt_execute($st);
        json_response(false, 'E-mail ou senha incorretos.');
    }

    // Sucesso: limpa tentativas, gira o ID da sessão (anti fixação) e novo CSRF.
    $st = mysqli_prepare($conn, "DELETE FROM login_tentativas WHERE email = ?");
    mysqli_stmt_bind_param($st, 's', $email);
    mysqli_stmt_execute($st);
    session_regenerate_id(true);
    $_SESSION['usuario_id']    = (int)$row['id'];
    $_SESSION['usuario_nome']  = $row['nome'];
    $_SESSION['usuario_email'] = $row['email'];
    $_SESSION['usuario_nivel'] = $row['nivel'];
    $_SESSION['trocar_senha']  = (int)$row['trocar_senha'];
    unset($_SESSION['csrf']);
    json_response(true, 'Login realizado com sucesso!', [
        'redirect' => BASE_URL . ((int)$row['trocar_senha'] ? '/trocar_senha.php' : '/dashboard.php'),
    ]);
}

if ($action === 'trocar_senha') {
    if (!is_logged()) json_response(false, 'Sessão expirada. Entre novamente.');
    $atual = (string)($_POST['atual'] ?? '');
    $nova  = (string)($_POST['nova'] ?? '');
    $conf  = (string)($_POST['confirma'] ?? '');
    if (strlen($nova) < 8) json_response(false, 'A nova senha precisa de ao menos 8 caracteres.');
    if ($nova !== $conf) json_response(false, 'A confirmação não confere com a nova senha.');
    if ($nova === 'admin123') json_response(false, 'Escolha uma senha diferente da senha padrão.');
    $id = (int)$_SESSION['usuario_id'];
    $st = mysqli_prepare($conn, "SELECT senha FROM usuarios WHERE id = ?");
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $hash = (string)(mysqli_fetch_assoc(mysqli_stmt_get_result($st))['senha'] ?? '');
    $confere = preg_match('/^[a-f0-9]{32}$/', $hash) ? hash_equals($hash, md5($atual)) : password_verify($atual, $hash);
    if (!$confere) json_response(false, 'Senha atual incorreta.');
    $novo = password_hash($nova, PASSWORD_DEFAULT);
    $st = mysqli_prepare($conn, "UPDATE usuarios SET senha = ?, trocar_senha = 0 WHERE id = ?");
    mysqli_stmt_bind_param($st, 'si', $novo, $id);
    mysqli_stmt_execute($st);
    $_SESSION['trocar_senha'] = 0;
    json_response(true, 'Senha alterada com sucesso!', ['redirect' => BASE_URL . '/dashboard.php']);
}

json_response(false, 'Ação inválida.');
