<?php
declare(strict_types=1);
$configFile = dirname(__DIR__) . '/config.php';
if (!is_file($configFile)) { http_response_code(503); exit('Configure o sistema seguindo o README.'); }
$config = require $configFile;
date_default_timezone_set($config['timezone'] ?? 'America/Sao_Paulo');
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly'=>true,'secure'=>$config['secure_cookie'] ?? true,'samesite'=>'Lax']);
session_start();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
header('Cache-Control: no-store');
try {
    $db = new PDO($config['dsn'], $config['user'] ?? null, $config['password'] ?? null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') $db->exec('PRAGMA foreign_keys = ON');
} catch (Throwable $e) { error_log($e->getMessage()); http_response_code(503); exit('Banco indisponível. Verifique a configuração.'); }
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function query(string $sql, array $args=[]): PDOStatement { global $db; $s=$db->prepare($sql); $s->execute($args); return $s; }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function redirect(string $url): never { header('Location: '.$url, true, 303); exit; }
function field(string $name, int $max=150): string { $v=trim((string)($_POST[$name] ?? '')); if ($v==='' || strlen($v)>$max) throw new RuntimeException('Preencha os campos corretamente.'); return $v; }
