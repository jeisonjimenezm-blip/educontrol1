<?php
/* Conexión a la base de datos con PDO */
require_once __DIR__ . '/../config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = getenv('EDU_DSN') ?: 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die('No se pudo conectar a la base de datos. Revise config.php y que MySQL esté encendido.');
        }
    }
    return $pdo;
}

/* Escapa texto para mostrarlo en HTML (evita XSS) */
function e(?string $texto): string
{
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

/* Nombre legible del tipo de programa */
function tipo_texto(string $tipo): string
{
    return ['curso' => 'Curso', 'tecnica' => 'Técnica', 'auxiliar' => 'Auxiliar'][$tipo] ?? $tipo;
}

/* Token CSRF para proteger los formularios */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function csrf_valido(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}
