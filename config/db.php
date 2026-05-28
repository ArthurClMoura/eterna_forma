<?php
/**
 * Conexão com o banco de dados (PDO / MySQL).
 * Ajuste as credenciais conforme o seu ambiente (XAMPP, WAMP, etc.).
 */

const DB_HOST = '127.0.0.1';
const DB_NAME = 'eterna_forma';
const DB_USER = 'root';
const DB_PASS = '';        // No XAMPP padrão a senha do root é vazia.
const DB_CHARSET = 'utf8mb4';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        exit('Erro ao conectar ao banco de dados. Verifique config/db.php. (' . $e->getMessage() . ')');
    }

    return $pdo;
}
