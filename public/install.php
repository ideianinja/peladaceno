<?php
declare(strict_types=1);
// Não confiar em cabeçalhos encaminhados enviados pelo navegador.
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') {
    http_response_code(403); exit('Abra esta página por HTTPS para instalar com segurança.');
}
require dirname(__DIR__).'/app/bootstrap.php';
$key=$config['install_key'] ?? '';
if (!is_string($key) || strlen($key)<32) {
    http_response_code(403); exit('Instalador desativado. Defina install_key no config.php com uma chave aleatória de pelo menos 32 caracteres.');
}
$error=''; $done=false;
try {
    // Apenas consulta: abrir a página não cria nem altera tabelas.
    $exists=query("SELECT COUNT(*) FROM admins")->fetchColumn();
    if ($exists) { http_response_code(403); exit('Instalação já realizada. Remova install.php da pasta pública e install_key do config.php.'); }
} catch (PDOException $ex) {
    // Somente ausência da tabela é esperada em um banco novo.
    if (!in_array((string)$ex->getCode(), ['42S02','HY000'], true) ||
        ((string)$ex->getCode()==='HY000' && !str_contains($ex->getMessage(),'no such table'))) {
        error_log($ex->getMessage()); http_response_code(503); exit('Não foi possível verificar o banco. Consulte o log PHP.');
    }
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals(csrf(),(string)($_POST['csrf'] ?? ''))) $error='Atualize a página e tente novamente.';
    elseif (!hash_equals($key,(string)($_POST['key'] ?? ''))) { http_response_code(403); $error='Chave de instalação incorreta.'; }
    else {
        $email=strtolower(trim((string)($_POST['email'] ?? '')));
        $password=(string)($_POST['password'] ?? '');
        if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>190) $error='Informe um e-mail válido.';
        elseif (strlen($password)<12 || strlen($password)>72) $error='Use uma senha entre 12 e 72 caracteres.';
        elseif (!hash_equals($password,(string)($_POST['confirmation'] ?? ''))) $error='As senhas não coincidem.';
        else {
            try {
                require dirname(__DIR__).'/app/schema.php';
                // ID fixo impede dois administradores em instalações simultâneas.
                query('INSERT INTO admins(id,email,password) VALUES (1,?,?)',[$email,password_hash($password,PASSWORD_DEFAULT)]);
                session_regenerate_id(true); $_SESSION=[]; $done=true;
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                $error='Não foi possível concluir. A instalação pode já ter sido realizada; tente entrar no painel ou consulte o log PHP.';
            }
        }
    }
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalar • Pelada Ceno</title><link rel="stylesheet" href="assets/style.css"></head><body><header><span class="brand">◉ Pelada Ceno</span></header><main><section class="card narrow">
<?php if ($done): ?><h1>Tudo pronto!</h1><p>Seu administrador foi criado. Remova o arquivo <strong>install.php</strong> da pasta pública e a entrada <strong>install_key</strong> do config.php pelo Gerenciador de Arquivos.</p><a class="button" href="index.php">Entrar no painel</a>
<?php else: ?><div class="eyebrow">PRIMEIRO ACESSO</div><h1>Vamos começar.</h1><p>Crie seu acesso de administrador. Os dados do banco já devem estar configurados.</p>
<?php if ($error): ?><p class="notice error" role="alert"><?=e($error)?></p><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><label>Chave de instalação<input type="password" name="key" required minlength="32" autocomplete="off"></label><label>Seu e-mail<input type="email" name="email" required maxlength="190" autocomplete="username"></label><label>Crie uma senha<input type="password" name="password" minlength="12" maxlength="72" required autocomplete="new-password"></label><label>Repita a senha<input type="password" name="confirmation" required autocomplete="new-password"></label><button>Instalar e criar administrador</button></form><small>Não envie a chave nem a senha pelo chat. Esta instalação bloqueia após criar o administrador.</small><?php endif; ?></section></main></body></html>
