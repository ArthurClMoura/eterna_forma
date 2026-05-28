<?php
/**
 * Helpers de sessão, autenticação e utilidades gerais.
 */

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* --------------------------------------------------------------
 * Polyfills para mbstring (caso a extensão não esteja habilitada).
 * No XAMPP padrão a mbstring já vem ativa; estes fallbacks apenas
 * garantem que o app funcione em qualquer instalação de PHP.
 * ------------------------------------------------------------ */
if (!function_exists('mb_substr')) {
    function mb_substr($s, $start, $length = null, $enc = 'UTF-8') {
        $a = preg_split('//u', (string)$s, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $slice = $length === null ? array_slice($a, $start) : array_slice($a, $start, $length);
        return implode('', $slice);
    }
}
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper($s, $enc = 'UTF-8') { return strtoupper((string)$s); }
}
if (!function_exists('mb_strimwidth')) {
    function mb_strimwidth($s, $start, $width, $marker = '', $enc = 'UTF-8') {
        $a = preg_split('//u', (string)$s, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($a) <= ($start + $width)) return implode('', array_slice($a, $start));
        return implode('', array_slice($a, $start, $width)) . $marker;
    }
}

/** Tipo de conta logada: 'desportista', 'equipe' ou null. */
function tipo_conta(): ?string
{
    return $_SESSION['tipo'] ?? null;
}

/** URL inicial conforme o tipo de conta. */
function home_url(): string
{
    return tipo_conta() === 'equipe' ? 'painel.php' : 'dashboard.php';
}

/** Retorna o ID do desportista logado (ou null se não for desportista). */
function usuario_logado(): ?int
{
    return tipo_conta() === 'desportista' ? ($_SESSION['usuario_id'] ?? null) : null;
}

/** Retorna o ID do membro da equipe logado (ou null). */
function membro_logado(): ?int
{
    return tipo_conta() === 'equipe' ? ($_SESSION['membro_id'] ?? null) : null;
}

/** Garante login de desportista; senão, redireciona. */
function exigir_login(): void
{
    if (!usuario_logado()) {
        header('Location: login.php');
        exit;
    }
}

/** Garante login de membro da equipe técnica; senão, redireciona. */
function exigir_equipe(): void
{
    if (!membro_logado()) {
        header('Location: login.php');
        exit;
    }
}

/** Garante que o membro logado é Gestor; senão, volta ao painel. */
function exigir_gestor(): void
{
    $m = membro_atual();
    if (!$m || $m['papel'] !== 'Gestor') {
        header('Location: painel.php');
        exit;
    }
}

/** Carrega os dados do membro da equipe logado (cacheado por requisição). */
function membro_atual(): ?array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $id = membro_logado();
    if (!$id) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM membros_equipe WHERE id = ?');
    $stmt->execute([$id]);
    $cache = $stmt->fetch() ?: null;
    return $cache;
}

/** Lista de categorias que o membro pode atender (Gestor = todas). */
function categorias_do_membro(array $m): array
{
    if ($m['papel'] === 'Gestor' || empty($m['categorias'])) {
        return ['Problema técnico', 'Dúvida', 'Sugestão'];
    }
    return array_values(array_filter(array_map('trim', explode(',', $m['categorias']))));
}

/** Carrega os dados básicos do usuário logado (cacheado por requisição). */
function usuario_atual(): ?array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $id = usuario_logado();
    if (!$id) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    $cache = $stmt->fetch() ?: null;
    return $cache;
}

/** Calcula idade a partir da data de nascimento (YYYY-MM-DD). */
function calcular_idade(string $dataNascimento): int
{
    try {
        $nasc = new DateTime($dataNascimento);
        return (int) $nasc->diff(new DateTime('today'))->y;
    } catch (Exception $e) {
        return 0;
    }
}

/** Escapa texto para saída segura em HTML. */
function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

/** Iniciais de um nome (para avatares). */
function iniciais(string $nome): string
{
    $nome = trim($nome);
    if ($nome === '') return '?';
    $p = preg_split('/\s+/', $nome) ?: [$nome];
    $i = mb_strtoupper(mb_substr($p[0], 0, 1));
    if (count($p) > 1) {
        $i .= mb_strtoupper(mb_substr(end($p), 0, 1));
    }
    return $i;
}

/** Envia uma resposta JSON e encerra. */
function json_resposta(array $dados, int $codigo = 200): void
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Calcula uma pontuação de compatibilidade (0–100) entre dois perfis fitness.
 * Baseada em objetivo, tipo de treino, nível, frequência e proximidade de idade.
 */
function compatibilidade(array $a, array $b): int
{
    $score = 0;

    // Mesmo objetivo principal: peso alto
    if (!empty($a['objetivo_principal']) && $a['objetivo_principal'] === $b['objetivo_principal']) {
        $score += 35;
    }
    // Mesmo tipo de treino
    if (!empty($a['tipo_treino_principal']) && $a['tipo_treino_principal'] === $b['tipo_treino_principal']) {
        $score += 30;
    }
    // Mesmo nível de experiência
    if (!empty($a['nivel_experiencia']) && $a['nivel_experiencia'] === $b['nivel_experiencia']) {
        $score += 15;
    }
    // Frequência semanal próxima (diferença de até 1 dia)
    if (!empty($a['frequencia_semanal']) && !empty($b['frequencia_semanal'])) {
        $diff = abs((int) $a['frequencia_semanal'] - (int) $b['frequencia_semanal']);
        if ($diff === 0)      $score += 10;
        elseif ($diff === 1)  $score += 6;
        elseif ($diff === 2)  $score += 3;
    }
    // Proximidade de idade
    if (!empty($a['idade']) && !empty($b['idade'])) {
        $diff = abs((int) $a['idade'] - (int) $b['idade']);
        if ($diff <= 3)       $score += 10;
        elseif ($diff <= 7)   $score += 6;
        elseif ($diff <= 12)  $score += 3;
    }

    return min(100, $score);
}
