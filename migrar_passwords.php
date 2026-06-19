<?php
// ===========================================================================
// migrar_passwords.php
// Executar UMA ÚNICA VEZ depois de atualizar o código para a versão
// que usa password_hash()/password_verify().
//
// Este script percorre a tabela "utilizadores" e converte qualquer password
// que ainda esteja em texto simples (ex: "1234") para um hash seguro.
// Passwords que já sejam hashes válidos são ignoradas, por isso é seguro
// correr este script mais do que uma vez sem partir nada.
//
// Depois de confirmar que o login funciona, podes apagar este ficheiro.
// ===========================================================================

$db = new SQLite3('database.db');

$res = $db->query("SELECT id_unico, password FROM utilizadores");
$convertidos = 0;
$ignorados = 0;

while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $password_atual = $row['password'];

    // Um hash do password_hash() começa sempre por $2y$ (ou $2a$/$2b$/$2x$)
    if (preg_match('/^\$2[axy]\$/', $password_atual)) {
        $ignorados++;
        continue;
    }

    $hash = password_hash($password_atual, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE utilizadores SET password = :hash WHERE id_unico = :id");
    $stmt->bindValue(':hash', $hash, SQLITE3_TEXT);
    $stmt->bindValue(':id', $row['id_unico'], SQLITE3_TEXT);
    $stmt->execute();
    $convertidos++;
}

echo "Migração concluída! $convertidos password(s) convertida(s) para hash. $ignorados já estavam em hash e foram ignoradas.";
$db->close();
?>
