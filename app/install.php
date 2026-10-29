<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/bootstrap.php';
require __DIR__.'/schema.php';
if (query('SELECT COUNT(*) FROM admins')->fetchColumn()) exit("Instalação já realizada.\n");
$email=getenv('ADMIN_EMAIL'); $password=getenv('ADMIN_PASSWORD');
if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password ?: '')<12) exit("Defina ADMIN_EMAIL e ADMIN_PASSWORD (mínimo 12 caracteres).\n");
query('INSERT INTO admins(id,email,password) VALUES (1,?,?)',[strtolower($email),password_hash($password,PASSWORD_DEFAULT)]);
echo "Instalação concluída.\n";
