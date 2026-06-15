<?php
$db = new SQLite3('database.db');

// Criar tabela de utilizadores funcional
$db->exec("CREATE TABLE IF NOT EXISTS utilizadores (
    id_unico TEXT PRIMARY KEY,
    password TEXT NOT NULL,
    tipo_acesso TEXT NOT NULL
)");

// Limpar dados antigos para o reteste limpo
$db->exec("DELETE FROM utilizadores");

// Inserir a nova conta mestre do AG corrigida para o formato correto
$stmt = $db->prepare("INSERT INTO utilizadores (id_unico, password, tipo_acesso) VALUES (:id, :pass, :tipo)");
$stmt->bindValue(':id', 'AG1', SQLITE3_TEXT);
$stmt->bindValue(':pass', '1234', SQLITE3_TEXT);
$stmt->bindValue(':tipo', 'AG', SQLITE3_TEXT);
$stmt->execute();

echo "Base de dados reiniciada com sucesso! Conta mestre configurada como 'AG1' com a password '1234'.";
$db->close();
?>