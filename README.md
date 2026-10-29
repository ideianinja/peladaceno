# Pelada Ceno

Primeira versão: painel de administrador, cadastro de jogadores, criação de peladas e confirmação por convite. Interface responsiva em português. PHP 8.2+ e PDO; MySQL no cPanel, SQLite opcional no desenvolvimento. Sem dependências Composer ou Node.

## Desenvolvimento

1. Copie `config.example.php` para `config.php`. Para desenvolvimento com SQLite, use `dsn => 'sqlite:/caminho/absoluto/storage/pelada.sqlite'`, usuário e senha nulos e `secure_cookie => false`. Não use cookies inseguros em produção.
2. Configure `ADMIN_EMAIL` e `ADMIN_PASSWORD` no processo de instalação (senha com pelo menos 12 caracteres). Execute `php app/install.php`. O instalador não altera um administrador existente e só funciona por linha de comando.
3. Execute `php -S 127.0.0.1:8080 -t public` na raiz. Os arquivos privados devem ficar fora da raiz pública. Use o checkout existente; não é necessário criar worktree.
4. Entre no painel, cadastre jogadores e crie uma pelada futura. Abra o convite e copie o endereço completo para compartilhar com o grupo.

## Publicar na ValueHost / cPanel

1. Faça backup de qualquer site já existente. Em **Selecionar versão do PHP** (o nome pode variar), habilite PHP 8.2 ou superior e PDO MySQL. Ative HTTPS/AutoSSL no domínio.
2. Crie banco e usuário no assistente MySQL. Vincule o usuário ao banco com as permissões necessárias; cPanel normalmente adiciona um prefixo aos nomes.
3. Envie o projeto para uma pasta privada da conta, por exemplo `/home/USUARIO/peladaceno`. Crie `config.php` a partir do exemplo com os dados reais do banco e `secure_cookie => true`. Não envie credenciais ao GitHub.
4. Configure a raiz de documentos do domínio/subdomínio como `/home/USUARIO/peladaceno/public`. Se seu plano só permitir `public_html`, mantenha `app`, `storage` e `config.php` fora dela; copie o conteúdo de `public` para `public_html` e ajuste **somente** o caminho do `require` no `index.php` para o caminho privado real de `app/bootstrap.php`. Não coloque o projeto inteiro em `public_html`.
5. Pelo Terminal/SSH do cPanel, entre na pasta privada e rode:

```bash
read -r -p 'E-mail do administrador: ' ADMIN_EMAIL
read -r -s -p 'Senha (mínimo 12 caracteres): ' ADMIN_PASSWORD
printf '\n'
export ADMIN_EMAIL ADMIN_PASSWORD
php app/install.php
unset ADMIN_EMAIL ADMIN_PASSWORD
```

Se o Terminal/SSH não estiver disponível, solicite ao suporte a execução segura da instalação. Não há instalador público pela web.

6. Acesse o domínio por HTTPS. Valide login, cadastro de jogador, criação de pelada e confirmação em uma janela anônima. Confira a presença no painel. Nunca compartilhe a senha administrativa.
7. Faça backups periódicos do MySQL e dos arquivos privados, armazene fora da pasta pública e teste restauração. Mantenha PHP atualizado. Desative `display_errors` em produção; consulte o log PHP em caso de erro.

## Escopo e limites

O convite funciona como acesso ao grupo: qualquer pessoa com o link pode ver os nomes cadastrados e alterar a resposta de qualquer jogador. Telefones não aparecem no convite. Compartilhe apenas com pessoas de confiança; esta versão não autentica jogadores individualmente. Confirmações encerram no horário da partida. O administrador pode consultar respostas de partidas anteriores.

Ainda não inclui recuperação de senha por e-mail, edição/exclusão de cadastros, pagamentos, sorteio de times ou notificações WhatsApp. A instalação não cria senha padrão. A proteção de login limita cinco erros por e-mail e IP em 15 minutos; para exposição ampla, complemente com proteção do provedor.

## Validação

`python3 tests/smoke.py http://127.0.0.1:8080` executa o fluxo HTTP completo contra um banco de teste recém-instalado com `admin@example.test` e a senha fornecida via `TEST_ADMIN_PASSWORD`. Nunca execute em produção: o teste cria jogador, pelada e confirmação.

## Instalação pelo navegador (sem Terminal)

Use um banco dedicado vazio e HTTPS ativo. Faça backup antes de atualizar arquivos. Envie também `app/schema.php` e `public/install.php`, preservando o caminho privado do bootstrap se adaptou a pasta pública. Nunca substitua seu `config.php` pelo exemplo.

1. No Gerenciador de Arquivos do cPanel, edite o `config.php` privado. Antes do `];`, adicione `'install_key' => 'SUA_CHAVE_ALEATORIA',`. Use o gerador de senhas do cPanel para gerar uma chave aleatória de 32 ou mais caracteres (letras e números). Não use o texto do exemplo como chave, nem envie a chave pelo chat.
2. Abra `https://peladaceno.ideianinja.com.br/install.php`. Informe a chave, seu e-mail e uma nova senha administrativa (12 a 72 caracteres). Não reutilize a senha do banco ou do cPanel.
3. Clique em instalar. Após a confirmação, remova `install.php` da pasta pública e remova `install_key` do `config.php`. Entre pelo endereço principal.

O instalador só funciona com HTTPS e chave configurada; valida a chave e o formulário antes de criar tabelas. Bloqueia quando já existe administrador. Não serve para recuperar ou redefinir senha. Se aparecer “Banco indisponível”, confira host, nome completo do banco, usuário, senha e permissões no cPanel sem compartilhar esses valores.
