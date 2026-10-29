<?php
// Compartilhado pelos instaladores CLI e web; nunca publica credenciais.
$mysql=$db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql';
$id=$mysql?'INTEGER PRIMARY KEY AUTO_INCREMENT':'INTEGER PRIMARY KEY AUTOINCREMENT';
$db->exec("CREATE TABLE IF NOT EXISTS admins (id $id, email VARCHAR(190) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL)");
$db->exec("CREATE TABLE IF NOT EXISTS players (id $id, name VARCHAR(100) NOT NULL, phone VARCHAR(30) NOT NULL DEFAULT '')");
$db->exec("CREATE TABLE IF NOT EXISTS games (id $id, title VARCHAR(150) NOT NULL, location VARCHAR(150) NOT NULL, starts_at VARCHAR(19) NOT NULL, token VARCHAR(64) NOT NULL UNIQUE)");
$db->exec('CREATE TABLE IF NOT EXISTS attendance (game_id INTEGER NOT NULL, player_id INTEGER NOT NULL, status VARCHAR(10) NOT NULL, PRIMARY KEY(game_id,player_id), FOREIGN KEY(game_id) REFERENCES games(id), FOREIGN KEY(player_id) REFERENCES players(id))');
$db->exec('CREATE TABLE IF NOT EXISTS login_attempts (identity_hash VARCHAR(64) PRIMARY KEY, attempts INTEGER NOT NULL, last_attempt INTEGER NOT NULL)');
