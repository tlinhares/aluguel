<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// ============================================================
// SEGURANÇA / SESSÃO
// ============================================================
function is_logged() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

function require_login() {
    if (!is_logged()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function is_admin() {
    return isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin';
}

// ============================================================
// SANITIZAÇÃO
// ============================================================
function sanitize($conn, $value) {
    return mysqli_real_escape_string($conn, strip_tags(trim($value)));
}

function sanitize_int($value) {
    return (int) $value;
}

function sanitize_float($value) {
    $value = str_replace(',', '.', $value);
    return (float) $value;
}

// ============================================================
// FORMATAÇÃO
// ============================================================
function format_money($value) {
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}

function format_date($date) {
    if (empty($date) || $date == '0000-00-00') return '-';
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d ? $d->format('d/m/Y') : $date;
}

function format_date_br_to_sql($date) {
    if (empty($date)) return null;
    $d = DateTime::createFromFormat('d/m/Y', $date);
    return $d ? $d->format('Y-m-d') : $date;
}

function format_cpf($cpf) {
    $cpf = preg_replace('/\D/', '', $cpf);
    return strlen($cpf) == 11 ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf) : $cpf;
}

function format_cnpj($cnpj) {
    $cnpj = preg_replace('/\D/', '', $cnpj);
    return strlen($cnpj) == 14 ? preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj) : $cnpj;
}

function format_phone($phone) {
    $phone = preg_replace('/\D/', '', $phone);
    if (strlen($phone) == 11) return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
    if (strlen($phone) == 10) return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $phone);
    return $phone;
}

function competencia_label($competencia) {
    if (empty($competencia)) return '-';
    $parts = explode('-', $competencia);
    if (count($parts) != 2) return $competencia;
    $meses = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
    return $meses[(int)$parts[1]] . '/' . $parts[0];
}

// ============================================================
// GERAÇÃO DE NÚMEROS
// ============================================================
// Usa o maior sequencial já emitido no ano (e não COUNT), para não repetir
// números após exclusões. O FOR UPDATE serializa emissões concorrentes
// quando chamado dentro de uma transação.
function proximo_numero($conn, $tabela, $prefixo, $digitos) {
    $ano = date('Y');
    $row = db_fetch_one($conn, "SELECT MAX(CAST(SUBSTRING_INDEX(numero, '-', -1) AS UNSIGNED)) as ultimo
                                FROM $tabela WHERE numero LIKE '{$prefixo}-{$ano}-%' FOR UPDATE");
    $seq = str_pad((int)($row['ultimo'] ?? 0) + 1, $digitos, '0', STR_PAD_LEFT);
    return "{$prefixo}-{$ano}-{$seq}";
}

function generate_contrato_numero($conn) {
    return proximo_numero($conn, 'contratos', 'CTR', 4);
}

function generate_recibo_numero($conn) {
    return proximo_numero($conn, 'recibos', 'REC', 5);
}

// ============================================================
// TRANSAÇÕES / VALIDAÇÃO
// ============================================================
// Erro de regra de negócio: a mensagem pode ser exibida ao usuário.
class RegraNegocioException extends Exception {}

function db_exec($conn, $sql) {
    $result = db_query($conn, $sql);
    if (!$result) throw new RuntimeException('Falha no banco: ' . mysqli_error($conn));
    return $result;
}

// Executa $fn numa transação; qualquer exceção desfaz tudo e é repassada.
function db_transacao($conn, callable $fn) {
    mysqli_begin_transaction($conn);
    try {
        $r = $fn();
        mysqli_commit($conn);
        return $r;
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        throw $e;
    }
}

// Responde JSON a partir de uma exceção: mensagens de regra de negócio vão
// para o usuário; erros técnicos só para o log.
function json_erro(Throwable $e) {
    if ($e instanceof RegraNegocioException) json_response(false, $e->getMessage());
    error_log('Erro: ' . $e->getMessage());
    json_response(false, 'Erro ao processar a operação. Tente novamente.');
}

function data_valida($data) {
    $d = DateTime::createFromFormat('!Y-m-d', (string)$data);
    return $d && $d->format('Y-m-d') === $data;
}

// ============================================================
// IMÓVEIS: STATUS CONFORME CONTRATOS
// ============================================================
// Imóvel com contrato ativo fica "alugado"; sem contrato ativo, um imóvel
// "alugado" volta a "disponível" (manutenção/inativo são preservados).
function sincronizar_status_imovel($conn, $imovel_id) {
    $imovel_id = (int)$imovel_id;
    if ($imovel_id <= 0) return;
    $ativos = db_fetch_one($conn, "SELECT COUNT(*) as c FROM contratos WHERE imovel_id = $imovel_id AND status = 'ativo'");
    if ($ativos && $ativos['c'] > 0) {
        db_exec($conn, "UPDATE imoveis SET status='alugado' WHERE id=$imovel_id");
    } else {
        db_exec($conn, "UPDATE imoveis SET status='disponivel' WHERE id=$imovel_id AND status='alugado'");
    }
}

// ============================================================
// PARCELAS: GERAÇÃO AUTOMÁTICA
// ============================================================
function gerar_parcelas($conn, $contrato_id, $data_inicio, $data_fim, $valor, $dia_vencimento) {
    $contrato_id = (int)$contrato_id;

    // Remove só parcelas em aberto (pendentes/atrasadas) sem recibo.
    // Pagas e canceladas são preservadas e suas competências não são recriadas.
    db_exec($conn, "DELETE p FROM parcelas p LEFT JOIN recibos r ON r.parcela_id = p.id
                    WHERE p.contrato_id = $contrato_id AND p.status IN ('pendente','atrasado') AND r.id IS NULL");
    $existentes = array_column(
        db_fetch_all($conn, "SELECT competencia FROM parcelas WHERE contrato_id = $contrato_id"),
        'competencia', 'competencia'
    );

    $inicio = new DateTime($data_inicio);
    $fim = new DateTime($data_fim);
    $dia = (int)$dia_vencimento;

    $current = new DateTime($inicio->format('Y-m-01'));

    while ($current <= $fim) {
        $ano = $current->format('Y');
        $mes = $current->format('m');
        $competencia = "$ano-$mes";
        if (isset($existentes[$competencia])) {
            $current->modify('+1 month');
            continue;
        }

        // Data de vencimento
        $max_day = (int)date('t', mktime(0, 0, 0, (int)$mes, 1, (int)$ano));
        $venc_dia = min($dia, $max_day);
        $data_venc = sprintf('%04d-%02d-%02d', $ano, $mes, $venc_dia);

        // Ajustar vencimento para não ser antes do início ou depois do fim do contrato
        if ($data_venc < $data_inicio) {
            $data_venc = $data_inicio;
        }
        if ($data_venc > $data_fim) {
            $data_venc = $data_fim;
        }

        $valor_esc = number_format($valor, 2, '.', '');
        $compet_esc = mysqli_real_escape_string($conn, $competencia);
        $sql = "INSERT INTO parcelas (contrato_id, competencia, data_vencimento, valor, status)
                VALUES ($contrato_id, '$compet_esc', '$data_venc', $valor_esc, 'pendente')";
        db_exec($conn, $sql);

        $current->modify('+1 month');
    }
}

// ============================================================
// STATUS BADGES
// ============================================================
function badge_status_parcela($status) {
    $map = [
        'pendente'  => 'warning',
        'pago'      => 'success',
        'atrasado'  => 'danger',
        'cancelado' => 'secondary',
    ];
    $color = $map[$status] ?? 'secondary';
    $labels = ['pendente'=>'Pendente','pago'=>'Pago','atrasado'=>'Atrasado','cancelado'=>'Cancelado'];
    $label = $labels[$status] ?? ucfirst($status);
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}

function badge_status_contrato($status) {
    $map = ['ativo'=>'success','encerrado'=>'secondary','rescindido'=>'danger','pendente'=>'warning'];
    $color = $map[$status] ?? 'secondary';
    $labels = ['ativo'=>'Ativo','encerrado'=>'Encerrado','rescindido'=>'Rescindido','pendente'=>'Pendente'];
    $label = $labels[$status] ?? ucfirst($status);
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}

function badge_status_imovel($status) {
    $map = ['disponivel'=>'success','alugado'=>'primary','manutencao'=>'warning','inativo'=>'secondary'];
    $color = $map[$status] ?? 'secondary';
    $labels = ['disponivel'=>'Disponível','alugado'=>'Alugado','manutencao'=>'Manutenção','inativo'=>'Inativo'];
    $label = $labels[$status] ?? ucfirst($status);
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}

function badge_status_manutencao($status) {
    $map = ['aberta'=>'danger','em_andamento'=>'warning','concluida'=>'success','cancelada'=>'secondary'];
    $color = $map[$status] ?? 'secondary';
    $labels = ['aberta'=>'Aberta','em_andamento'=>'Em Andamento','concluida'=>'Concluída','cancelada'=>'Cancelada'];
    $label = $labels[$status] ?? ucfirst($status);
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}

function badge_prioridade_manutencao($prioridade) {
    $map = ['baixa'=>'secondary','media'=>'info','alta'=>'warning','urgente'=>'danger'];
    $color = $map[$prioridade] ?? 'secondary';
    $labels = ['baixa'=>'Baixa','media'=>'Média','alta'=>'Alta','urgente'=>'Urgente'];
    $label = $labels[$prioridade] ?? ucfirst($prioridade);
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}

// ============================================================
// ENCARGOS POR ATRASO (sugestão a partir de `configuracoes`)
// ============================================================
// Multa fixa (%) + juros mensais (%) pro rata dia, sobre o valor da parcela.
function calcular_encargos($conn, $valor, $data_vencimento, $data_pagamento = null) {
    $data_pagamento = $data_pagamento ?: date('Y-m-d');
    $dias = (int)(new DateTime($data_vencimento))->diff(new DateTime($data_pagamento))->format('%r%a');
    if ($dias <= 0) return ['dias_atraso' => 0, 'multa' => 0.0, 'juros' => 0.0];
    $multa_pct = (float)str_replace(',', '.', get_config($conn, 'multa_atraso'));
    $juros_pct = (float)str_replace(',', '.', get_config($conn, 'juros_atraso'));
    return [
        'dias_atraso' => $dias,
        'multa' => round($valor * $multa_pct / 100, 2),
        'juros' => round($valor * $juros_pct / 100 * $dias / 30, 2),
    ];
}

// ============================================================
// ATUALIZAR STATUS DE PARCELAS ATRASADAS
// ============================================================
function atualizar_parcelas_atrasadas($conn) {
    $hoje = date('Y-m-d');
    db_query($conn, "UPDATE parcelas SET status = 'atrasado' WHERE status = 'pendente' AND data_vencimento < '$hoje'");
}

// ============================================================
// CONFIGURAÇÕES
// ============================================================
function get_config($conn, $chave) {
    $chave = mysqli_real_escape_string($conn, $chave);
    $row = db_fetch_one($conn, "SELECT valor FROM configuracoes WHERE chave = '$chave'");
    return $row ? $row['valor'] : '';
}

function json_response($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}
