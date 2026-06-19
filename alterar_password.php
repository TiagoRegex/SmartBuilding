<?php
session_start();

// Proteção: Garante que o utilizador está logado para mudar a password
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_tipo = $_SESSION['user_tipo'];
$db = new SQLite3('database.db');

// Define o link de retorno com base no tipo de utilizador
$link_retorno = "dashboard_clt.php";
if ($user_tipo === 'AG') {
    $link_retorno = "dashboard_ag.php";
} elseif ($user_tipo === 'AL') {
    $link_retorno = "dashboard_al.php";
}

// LÓGICA DE ATUALIZAÇÃO DA PASSWORD
if (isset($_POST['mudar_pass'])) {
    $pass_atual = $_POST['pass_atual'];
    $nova_pass = $_POST['nova_pass'];

    // Verifica se a password atual está correta
    $stmt = $db->prepare("SELECT password FROM utilizadores WHERE id_unico = :id");
    $stmt->bindValue(':id', $user_id, SQLITE3_TEXT);
    $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

   if ($user && password_verify($pass_atual, $user['password'])) {
        // Atualiza para a nova password (guardada como hash)
        $novo_hash = password_hash($nova_pass, PASSWORD_DEFAULT);
        $update = $db->prepare("UPDATE utilizadores SET password = :nova WHERE id_unico = :id");
        $update->bindValue(':nova', $novo_hash, SQLITE3_TEXT);
        $update->bindValue(':id', $user_id, SQLITE3_TEXT);
        $update->execute();

        header("Location: $link_retorno?pass_alterada=1");
        exit();
    } else {
        header("Location: alterar_password.php?erro_pass=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Password</title>
    <link rel="stylesheet" href="css/styles.css?v=1.4">
</head>
<body>

    <div class="app-wrapper">
        
        <aside class="sidebar">
            <div>
                <div class="sidebar-header">
                    🔒 Segurança
                </div>
                <ul class="sidebar-menu">
                    <li class="sidebar-item active">
                        <a href="#">🔑 Alterar Dados</a>
                    </li>
                </ul>
            </div>
            <div class="sidebar-footer">
                <div>Utilizador: <strong><?php echo $user_id; ?></strong></div>
                <a href="<?php echo $link_retorno; ?>" class="btn-sidebar-action" style="background:#64748b; border:none;">← Cancelar / Voltar</a>
            </div>
        </aside>

        <div class="main-content">
            <header class="top-bar">
                <div><span style="color:#64748b; font-weight:500;">Definições de Conta de Utilizador</span></div>
            </header>

            <main class="container" style="display:flex; justify-content:center; align-items:center;">
                
                <div class="card" style="flex: 0 1 450px; margin-top: 2rem;">
                    <h3 class="card-title">Modificar Palavra-Passe</h3>
                    <p style="color:#64748b; font-size:0.9rem; margin-bottom:1.5rem;">Por motivos de segurança, introduza os dados abaixo:</p>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Palavra-Passe Atual:</label>
                            <input type="password" name="pass_atual" placeholder="Digite a password atual" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Nova Palavra-Passe:</label>
                            <input type="password" name="nova_pass" placeholder="Digite a nova password" required>
                        </div>
                        
                        <button type="submit" name="mudar_pass" class="btn-submit" style="margin-top:0.5rem;">Gravar Nova Password</button>
                    </form>
                </div>

            </main>
        </div>

    </div>
 <script src="js/mensagens.js"></script>
</body>
</html>
<?php $db->close(); ?>