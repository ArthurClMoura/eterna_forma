<?php
/**
 * API de favoritos (JSON). Alterna favoritar/desfavoritar.
 */
require_once __DIR__ . '/../includes/auth.php';

if (!usuario_logado())              json_resposta(['ok' => false, 'erro' => 'Não autenticado.'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_resposta(['ok' => false, 'erro' => 'Método inválido.'], 405);

$uid  = usuario_logado();
$body = json_decode(file_get_contents('php://input'), true) ?: [];
$alvo = (int) ($body['alvo'] ?? 0);

if ($alvo <= 0 || $alvo === $uid) json_resposta(['ok'=>false,'erro'=>'Alvo inválido.'], 422);

try {
    $st = db()->prepare('SELECT id FROM favoritos WHERE usuario_id=? AND usuario_favoritado_id=?');
    $st->execute([$uid, $alvo]);
    $existente = $st->fetch();

    if ($existente) {
        db()->prepare('DELETE FROM favoritos WHERE id=?')->execute([$existente['id']]);
        json_resposta(['ok' => true, 'favoritado' => false]);
    }

    db()->prepare('INSERT INTO favoritos (usuario_id, usuario_favoritado_id) VALUES (?, ?)')
        ->execute([$uid, $alvo]);
    json_resposta(['ok' => true, 'favoritado' => true]);

} catch (Throwable $e) {
    json_resposta(['ok' => false, 'erro' => 'Erro interno.'], 500);
}
