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

// A password é guardada como hash sem plain text
$hash_inicial = password_hash('1234', PASSWORD_DEFAULT);
$stmt = $db->prepare("INSERT INTO utilizadores (id_unico, password, tipo_acesso) VALUES (:id, :pass, :tipo)");
$stmt->bindValue(':id', 'AG1', SQLITE3_TEXT);
// $hash_inicial serve como hash da password, garantindo segurança
$stmt->bindValue(':pass', $hash_inicial, SQLITE3_TEXT);
$stmt->bindValue(':tipo', 'AG', SQLITE3_TEXT);
$stmt->execute();

echo "Base de dados reiniciada com sucesso! Conta mestre configurada como 'AG1' com a password '1234'.";
$db->close();
?>