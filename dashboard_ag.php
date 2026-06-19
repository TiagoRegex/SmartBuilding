<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'AG') {
    header("Location: index.html");
    exit();
}

$db = new SQLite3('database.db');

if (isset($_POST['criar_usuario'])) {
    $novo_id = strtoupper(trim($_POST['novo_id']));
    $nova_pass = $_POST['nova_pass'];
    $tipo_user = $_POST['tipo_user'];

     // Só permite criar contas com um tipo de acesso válido
    $tipos_validos = ['CLT', 'AL', 'AG'];
    if (in_array($tipo_user, $tipos_validos, true) && $novo_id !== '') {
        // A password nunca é guardada em texto simples
        $hash = password_hash($nova_pass, PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT OR IGNORE INTO utilizadores (id_unico, password, tipo_acesso) VALUES (:id, :pass, :tipo)");
        $stmt->bindValue(':id', $novo_id, SQLITE3_TEXT);
        $stmt->bindValue(':pass', $hash, SQLITE3_TEXT);
        $stmt->bindValue(':tipo', $tipo_user, SQLITE3_TEXT);
        $stmt->execute();
    }
    header("Location: dashboard_ag.php");
    exit();
}

if (isset($_GET['eliminar'])) {
    $id_eliminar = $_GET['eliminar'];
    if ($id_eliminar !== $_SESSION['user_id']) {
        $stmt = $db->prepare("DELETE FROM utilizadores WHERE id_unico = :id");
        $stmt->bindValue(':id', $id_eliminar, SQLITE3_TEXT);
        $stmt->execute();
    }
    header("Location: dashboard_ag.php");
    exit();
}

$usuarios = $db->query("SELECT * FROM utilizadores");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Admin Geral</title>
    <link rel="stylesheet" href="css/styles.css?v=1.1">
</head>
<body>

    <div class="app-wrapper">
        
        <aside class="sidebar">
            <div>
                <div class="sidebar-header">🛡️AG </div>
                <ul class="sidebar-menu">
                    <li class="sidebar-item active"><a href="dashboard_ag.php">👥 Gestão de Contas</a></li>
                    <li class="sidebar-item"><a href="dashboard_al.php?edificio=E1">🏢 Edifício E1</a></li>
                    <li class="sidebar-item"><a href="dashboard_al.php?edificio=E2">🏢 Edifício E2</a></li>
                </ul>
            </div>
            
            <div class="sidebar-footer">
                <div>Ligado como: <strong><?php echo $_SESSION['user_id']; ?></strong></div>
                <a href="alterar_password.php" class="btn-sidebar-action">🔑 Alterar Password</a>
                <a href="index.html" class="btn-sidebar-action btn-sidebar-logout">🚪 Sair</a>
            </div>
        </aside>

        <div class="main-content">
            <header class="top-bar">
                <div><span style="color:#64748b; font-weight:500;">Painel de Controlo Administrador Geral</span></div>
            </header>

            <main class="container">
                <h2>Painel de Controlo Global</h2>
                         
                <div class="grid-layout">
                    <div class="card" style="flex: 1 1 400px;">
                        <h3 class="card-title">Criar Nova Conta</h3>
                        <form method="POST">
                            <div class="form-group">
                                <label>Nível de Acesso:</label>
                                <select id="tipo_user" name="tipo_user" onchange="atualizarExemplo()">
                                    <option value="CLT">Cliente (CLT)</option>
                                    <option value="AL">Admin Local (AL)</option>
                                    <option value="AG">Admin Geral (AG)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>ID Único:</label>
                                <input type="text" id="novo_id" name="novo_id" placeholder="Ex: E1A1CLT1" required>
                            </div>
                            <div class="form-group">
                                <label>Password:</label>
                                <input type="text" name="nova_pass" value="1234" required>
                            </div>
                            <button type="submit" name="criar_usuario" class="btn-submit">Criar Utilizador</button>
                        </form>
                    </div>

                    <div class="card" style="flex: 1 1 500px;">
                        <h3 class="card-title">Utilizadores Registados</h3>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr><th>ID Único</th><th>Password</th><th>Nível</th><th>Ações</th></tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $usuarios->fetchArray(SQLITE3_ASSOC)): ?>
                                    <tr>
                                        <td><?php echo $row['id_unico']; ?></td>
                                        <td><em style="color:#64748b;">(encriptada)</em></td>
                                        <td><strong><?php echo $row['tipo_acesso']; ?></strong></td>
                                        <td>
                                            <?php if ($row['id_unico'] !== $_SESSION['user_id']): ?>
                                                <a href="dashboard_ag.php?eliminar=<?php echo $row['id_unico']; ?>" onclick="return confirm('Apagar utilizador?')" style="color:#ef4444; text-decoration:none; font-weight:bold;">[Apagar]</a>
                                            <?php else: ?>
                                                <span style="color:#64748b;">(Atual)</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
     <script src="js/dashboard_ag.js"></script>
</body>
</html>
<?php $db->close(); ?>