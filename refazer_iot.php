<?php
$db = new SQLite3('database.db');

// Recriar tabela com campo divisão e sem subtipo
$db->exec("DROP TABLE IF EXISTS equipamentos_iot");
$db->exec("CREATE TABLE equipamentos_iot (
    id_equipamento INTEGER PRIMARY KEY AUTOINCREMENT,
    tipo TEXT NOT NULL,          -- 'Iluminação', 'Climatização', 'Estores', 'Forno', etc.
    estado TEXT DEFAULT 'OFF',   
    id_edificio TEXT NOT NULL,   
    id_apartamento TEXT NOT NULL,
    divisao TEXT NOT NULL        -- 'Sala', 'Cozinha', 'Quarto', 'WC'
)");

echo "Tabela IoT reconstruída com suporte a divisões e sem subtipo!";
$db->close();
?>