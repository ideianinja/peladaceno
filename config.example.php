<?php
return [
    // MySQL no cPanel. Copie para config.php, fora da pasta pública.
    'dsn' => 'mysql:host=localhost;dbname=SEU_BANCO;charset=utf8mb4',
    'user' => 'SEU_USUARIO',
    'password' => 'SUA_SENHA',
    'timezone' => 'America/Sao_Paulo',
    // Opcional: gere no cPanel uma chave aleatória de 32+ caracteres. Remova após instalar.
    'install_key' => '',
    'secure_cookie' => true,
];
