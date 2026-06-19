<?php
session_start();

// Garante que os dados vieram por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new SQLite3('database.db');

    // Força o ID recebido a ir para Maiúsculas, eliminando erros de digitação
    $user_id = strtoupper(trim($_POST['user_id']));
    $password = $_POST['password'];

    // Procura o utilizador na base de dados
    $stmt = $db->prepare("SELECT * FROM utilizadores WHERE id_unico = :id");
    $stmt->bindValue(':id', $user_id, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    // Valida a password (HASH) e o tipo de acesso
     if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id_unico'];
        $_SESSION['user_tipo'] = $user['tipo_acesso'];

        // Redireciona para o painel correto conforme o nível
        if ($user['tipo_acesso'] === 'AG') {
            header("Location: dashboard_ag.php");
        } elseif ($user['tipo_acesso'] === 'AL') {
            header("Location: dashboard_al.php");
        } elseif ($user['tipo_acesso'] === 'CLT') {
            header("Location: dashboard_clt.php");
        }
        exit();
    } else {
        // js/mensagens.js mostra o alerta
        header("Location: index.html?erro_login=1");
        exit();
    }
    $db->close();
} else {
    header("Location: index.html");
    exit();
}
?> 