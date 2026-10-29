<?php
require dirname(__DIR__).'/app/bootstrap.php';
$error=''; $invite=(string)($_GET['convite'] ?? '');
try {
if ($_SERVER['REQUEST_METHOD']==='POST') {
 if (!hash_equals(csrf(),(string)($_POST['csrf'] ?? ''))) throw new RuntimeException('Sua sessão expirou. Atualize a página.');
 $action=(string)($_POST['action'] ?? '');
 if ($action==='login') {
  $email=strtolower(field('email',190)); $key=hash('sha256',($_SERVER['REMOTE_ADDR'] ?? '').'|'.$email);
  $attempt=query('SELECT * FROM login_attempts WHERE identity_hash=?',[$key])->fetch();
  if ($attempt && $attempt['attempts']>=5 && time()-$attempt['last_attempt']<900) throw new RuntimeException('Muitas tentativas. Aguarde 15 minutos.');
  $admin=query('SELECT * FROM admins WHERE email=?',[$email])->fetch();
  if (!$admin || !password_verify((string)($_POST['password'] ?? ''),$admin['password'])) {
   $count=$attempt && time()-$attempt['last_attempt']<900 ? $attempt['attempts']+1 : 1;
   if ($attempt) query('UPDATE login_attempts SET attempts=?, last_attempt=? WHERE identity_hash=?',[$count,time(),$key]);
   else query('INSERT INTO login_attempts VALUES (?,?,?)',[$key,$count,time()]);
   throw new RuntimeException('E-mail ou senha incorretos.');
  }
  query('DELETE FROM login_attempts WHERE identity_hash=?',[$key]); session_regenerate_id(true); $_SESSION['admin']=$admin['id']; redirect('?');
 }
 elseif ($action==='attendance') {
  $game=query('SELECT * FROM games WHERE token=?',[$invite])->fetch();
  if (!$game || $game['starts_at']<date('Y-m-d H:i:s')) throw new RuntimeException('Esta confirmação está encerrada.');
  $player=(int)($_POST['player'] ?? 0); $status=(string)($_POST['status'] ?? '');
  if (!in_array($status,['yes','no'],true) || !query('SELECT id FROM players WHERE id=?',[$player])->fetch()) throw new RuntimeException('Selecione um jogador e sua resposta.');
  $db->beginTransaction(); query('DELETE FROM attendance WHERE game_id=? AND player_id=?',[$game['id'],$player]); query('INSERT INTO attendance VALUES (?,?,?)',[$game['id'],$player,$status]); $db->commit();
  $_SESSION['message']='Presença atualizada!'; redirect('?convite='.urlencode($invite));
 }
 else {
  if (empty($_SESSION['admin'])) { http_response_code(403); throw new RuntimeException('Entre como administrador.'); }
  if ($action==='logout') { $_SESSION=[]; session_regenerate_id(true); redirect('?'); }
  elseif ($action==='player') { query('INSERT INTO players(name,phone) VALUES (?,?)',[field('name',100),trim(substr((string)($_POST['phone'] ?? ''),0,30))]); $_SESSION['message']='Jogador cadastrado!'; }
  elseif ($action==='game') {
   $date=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',field('starts_at',16));
   if (!$date || $date->format('Y-m-d\TH:i')!==$_POST['starts_at'] || $date->getTimestamp()<=time()) throw new RuntimeException('Escolha uma data futura válida.');
   query('INSERT INTO games(title,location,starts_at,token) VALUES (?,?,?,?)',[field('title'),field('location'),$date->format('Y-m-d H:i:s'),bin2hex(random_bytes(24))]); $_SESSION['message']='Pelada criada! Compartilhe o link de confirmação.';
  } else throw new RuntimeException('Ação inválida.');
  redirect('?');
 }
}
} catch (RuntimeException $ex) { if ($db->inTransaction()) $db->rollBack(); if ($ex instanceof PDOException) { error_log($ex->getMessage()); $error='Não foi possível salvar. Tente novamente.'; } else $error=$ex->getMessage(); }
$players=query('SELECT * FROM players ORDER BY name')->fetchAll();
$message=$_SESSION['message'] ?? ''; unset($_SESSION['message']);
function hidden(string $action): void { echo '<input type="hidden" name="csrf" value="'.e(csrf()).'"><input type="hidden" name="action" value="'.e($action).'">'; }
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pelada Ceno • Bora jogar</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header><a class="brand" href="?">◉ Pelada<span>Ceno</span></a><span class="tag">FUTEBOL ENTRE AMIGOS</span><?php if (!empty($_SESSION['admin'])): ?><form method="post"><?php hidden('logout'); ?><button class="ghost">Sair</button></form><?php endif; ?></header>
<main><?php if ($error): ?><p class="notice error" role="alert"><?=e($error)?></p><?php endif; ?><?php if ($message): ?><p class="notice" role="status"><?=e($message)?></p><?php endif; ?>
<?php if ($invite): $game=query('SELECT * FROM games WHERE token=?',[$invite])->fetch(); ?>
<?php if (!$game): http_response_code(404); ?><h1>Convite não encontrado</h1><p>Confira o link com o organizador.</p>
<?php else: ?><div class="eyebrow">A PRÓXIMA PARTIDA TEM SEU NOME</div><h1><?=e($game['title'])?></h1><p class="subtitle"><?=e(date('d/m/Y • H:i',strtotime($game['starts_at'])))?> · <?=e($game['location'])?></p>
<section class="card narrow"><h2>Bora jogar?</h2><p>Escolha seu nome e avise o time.</p><?php if ($game['starts_at']<date('Y-m-d H:i:s')): ?><p>Confirmação encerrada.</p><?php else: ?><form method="post"><?php hidden('attendance'); ?><label>Seu nome<select name="player" required><option value="">Selecione seu nome</option><?php foreach ($players as $p): ?><option value="<?=e($p['id'])?>"><?=e($p['name'])?></option><?php endforeach; ?></select></label><div class="actions"><button name="status" value="yes">✓ Vou jogar</button><button name="status" value="no" class="ghost">Não vou</button></div></form><small>O link permite atualizar a resposta de qualquer jogador. Compartilhe apenas com o grupo.</small><?php endif; ?></section><?php endif; ?>
<?php elseif (empty($_SESSION['admin'])): ?>
<div class="login"><div><div class="eyebrow">MENOS PLANILHA. MAIS FUTEBOL.</div><h1>Seu time.<br>Sua pelada.<br><em>Tudo no jeito.</em></h1><p class="subtitle">Organize a próxima partida e saiba quem vai entrar em campo.</p><div class="pitch">⚽<span>O encontro da semana começa aqui.</span></div></div><section class="card"><div class="eyebrow">ÁREA DO ORGANIZADOR</div><h2>Bom ter você de volta.</h2><p>Entre para organizar a resenha.</p><form method="post"><?php hidden('login'); ?><label>E-mail<input type="email" name="email" autocomplete="username" required maxlength="190"></label><label>Senha<input type="password" name="password" autocomplete="current-password" required></label><button>Entrar no painel →</button></form><small>Acesso exclusivo do administrador.</small></section></div>
<?php else: $games=query('SELECT * FROM games ORDER BY starts_at DESC')->fetchAll(); ?>
<div class="eyebrow">PAINEL DO ORGANIZADOR</div><h1>Deixe a bola rolar<span class="green">.</span></h1><p class="subtitle">Seu grupo organizado. A próxima pelada garantida.</p>
<div class="stats"><div><strong><?=count($players)?></strong> jogadores cadastrados</div><div><strong><?=count(array_filter($games,fn($g)=>$g['starts_at']>=date('Y-m-d H:i:s')))?></strong> peladas pela frente</div></div>
<div class="columns"><section><h2>Agenda da pelada</h2><?php if (!$games): ?><div class="card empty"><span>⚽</span><h3>O campo está esperando.</h3><p>Crie sua primeira pelada no formulário ao lado.</p></div><?php endif; ?>
<?php foreach ($games as $g): $responses=query('SELECT p.name,a.status FROM attendance a JOIN players p ON p.id=a.player_id WHERE game_id=? ORDER BY p.name',[$g['id']])->fetchAll(); $yes=count(array_filter($responses,fn($r)=>$r['status']==='yes')); ?><article class="card game"><div class="eyebrow"><?=e(date('d/m/Y • H:i',strtotime($g['starts_at'])))?></div><h3><?=e($g['title'])?></h3><p>⌖ <?=e($g['location'])?></p><div class="score"><?=e($yes)?> confirmados <span>· <?=count($players)-count($responses)?> sem resposta</span></div><a class="button ghost" href="?convite=<?=e($g['token'])?>">Abrir confirmação ↗</a><label>Link para compartilhar<input readonly value="<?=e('?convite='.$g['token'])?>" aria-label="Caminho do convite"></label><small>Abra o convite e copie o endereço completo do navegador.</small><details><summary>Ver respostas (<?=count($responses)?>)</summary><?php foreach ($responses as $r): ?><p><?=e($r['name'])?> <span class="badge"><?=$r['status']==='yes'?'Vai jogar':'Não vai'?></span></p><?php endforeach; ?></details></article><?php endforeach; ?></section>
<aside><section class="card"><h2>＋ Nova pelada</h2><form method="post"><?php hidden('game'); ?><label>Nome da pelada<input name="title" placeholder="Pelada de quinta" maxlength="150" required></label><label>Local<input name="location" placeholder="Quadra, campo ou arena" maxlength="150" required></label><label>Data e horário<input type="datetime-local" name="starts_at" required></label><button>Criar pelada →</button></form></section><section class="card"><h2>＋ Novo jogador</h2><form method="post"><?php hidden('player'); ?><label>Nome<input name="name" maxlength="100" required></label><label>Telefone <span class="muted">(opcional)</span><input type="tel" name="phone" maxlength="30" autocomplete="tel"></label><button>Cadastrar jogador</button></form></section></aside></div>
<section class="card"><h2>Elenco <span class="muted">/ <?=count($players)?></span></h2><?php if (!$players): ?><p>Cadastre os amigos para começar.</p><?php endif; ?><div class="roster"><?php foreach ($players as $p): ?><div><span class="avatar"><?=e(strtoupper(substr($p['name'],0,1)))?></span><div><strong><?=e($p['name'])?></strong><small><?=e($p['phone'] ?: 'Sem telefone')?></small></div></div><?php endforeach; ?></div></section>
<?php endif; ?></main><footer>PELADA CENO <span>Feito para juntar o time.</span></footer></body></html>
