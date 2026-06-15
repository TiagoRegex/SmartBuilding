<?php
$db = new SQLite3('database.db');

// 1. Tabela de Edifícios (Para saber qual AL manda em qual prédio)
$db->exec("CREATE TABLE IF NOT EXISTS edificios (
    id_edificio TEXT PRIMARY KEY, -- Ex: 'E1', 'E2'
    morada TEXT NOT NULL,
    al_responsavel TEXT, -- ID do AL, ex: 'E1AL1'
    FOREIGN KEY (al_responsavel) REFERENCES utilizadores(id_unico)
)");

// 2. Tabela de Equipamentos IoT
$db->exec("CREATE TABLE IF NOT EXISTS equipamentos_iot (
    id_equipamento INTEGER PRIMARY KEY AUTOINCREMENT,
    tipo TEXT NOT NULL,          -- 'Temperatura', 'Iluminação', 'Estores'
    subtipo TEXT NOT NULL,       -- 'Sensor' ou 'Atuador'
    estado TEXT DEFAULT 'OFF',   -- 'ON', 'OFF', ou valor numérico como '22.5'
    id_edificio TEXT NOT NULL,   -- Ex: 'E1'
    id_apartamento TEXT NOT NULL,-- Ex: 'A1', 'A2'
    FOREIGN KEY (id_edificio) REFERENCES edificios(id_edificio)
)");

// Inserir um edifício de teste para o teu primeiro AL
$db->exec("INSERT OR IGNORE INTO edificios (id_edificio, morada, al_responsavel) 
          VALUES ('E1', 'Rua dos IoTs, Nº 40, Lisboa', 'E1AL1')");

echo "Tabelas de Edifícios e IoT criadas com sucesso!";
$db->close();
?>