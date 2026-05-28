<?php
/**
 * API de matches (JSON).
 * Ações: solicitar, aceitar, rejeitar, desfazer
 */
require_once __DIR__ . '/../includes/auth.php';

if (!usuario_logado())              json_resposta(['ok' => false, 'erro' => 'Não autenticado.'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_resposta(['ok' => false, 'erro' => 'Método inválido.'], 405);

$uid    = usuario_logado();
$body   = json_decode(file_get_contents('php://input'), true) ?: [];
$acao   = $body['acao'] ?? '';
$alvo   = (int) ($body['alvo'] ?? 0);
$matchId = (int) ($body['match_id'] ?? 0);

try {
    switch ($acao) {

        case 'solicitar':
            if ($alvo <= 0 || $alvo === $uid) json_resposta(['ok'=>false,'erro'=>'Alvo inválido.'], 422);

            // Já existe match nesse par?
            $st = db()->prepare(
                'SELECT id FROM matches
                 WHERE (usuario1_id=? AND usuario2_id=?) OR (usuario1_id=? AND usuario2_id=?)'
            );
            $st->execute([$uid, $alvo, $alvo, $uid]);
            if ($st->fetch()) json_resposta(['ok'=>false,'erro'=>'Já existe uma conexão com este usuário.'], 409);

            db()->prepare(
                "INSERT INTO matches (usuario1_id, usuario2_id, status, mensagem_pessoal)
                 VALUES (?, ?, 'pendente', ?)"
            )->execute([$uid, $alvo, $body['mensagem'] ?? null]);

            json_resposta(['ok' => true, 'status' => 'pendente']);

        case 'aceitar':
        case 'rejeitar':
            $novo = $acao === 'aceitar' ? 'aceito' : 'rejeitado';
            // Só o destinatário (usuario2) pode responder
            $st = db()->prepare("SELECT * FROM matches WHERE id=? AND usuario2_id=? AND status='pendente'");
            $st->execute([$matchId, $uid]);
            if (!$st->fetch()) json_resposta(['ok'=>false,'erro'=>'Solicitação não encontrada.'], 404);

            db()->prepare("UPDATE matches SET status=?, respondido_em=NOW() WHERE id=?")
                ->execute([$novo, $matchId]);

            json_resposta(['ok' => true, 'status' => $novo]);

        case 'desfazer':
            // Qualquer um dos dois lados pode desfazer
            $st = db()->prepare(
                "SELECT * FROM matches WHERE id=? AND (usuario1_id=? OR usuario2_id=?) AND status='aceito'"
            );
            $st->execute([$matchId, $uid, $uid]);
            if (!$st->fetch()) json_resposta(['ok'=>false,'erro'=>'Match não encontrado.'], 404);

            db()->prepare("UPDATE matches SET status='desfeito' WHERE id=?")->execute([$matchId]);
            json_resposta(['ok' => true, 'status' => 'desfeito']);

        default:
            json_resposta(['ok' => false, 'erro' => 'Ação desconhecida.'], 422);
    }
} catch (Throwable $e) {
    json_resposta(['ok' => false, 'erro' => 'Erro interno.'], 500);
}
