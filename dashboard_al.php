<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_tipo'] !== 'AL' && $_SESSION['user_tipo'] !== 'AG')) {
    header("Location: index.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_tipo = $_SESSION['user_tipo'];
$db = new SQLite3('database.db');

if ($user_tipo === 'AG') {
    $meu_edificio = isset($_GET['edificio']) ? strtoupper(trim($_GET['edificio'])) : 'E1';
} else {
    preg_match('/^(E\d+)/i', $user_id, $matches);
    $meu_edificio = isset($matches[1]) ? strtoupper($matches[1]) : 'E1';
}

$tab_ativa = isset($_GET['tab']) ? $_GET['tab'] : 'apartamentos';

if (isset($_GET['eliminar_clt'])) {
    $id_eliminar = $_GET['eliminar_clt'];
    $db->exec("DELETE FROM utilizadores WHERE id_unico = '$id_eliminar' AND tipo_acesso = 'CLT'");
    header("Location: dashboard_al.php?edificio=$meu_edificio&tab=contas");
    exit();
}

if (isset($_POST['criar_clt'])) {
    $novo_clt_id = strtoupper(trim($_POST['clt_id']));
    $clt_pass = $_POST['clt_pass'];
    $db->exec("INSERT OR IGNORE INTO utilizadores (id_unico, password, tipo_acesso) VALUES ('$novo_clt_id', '$clt_pass', 'CLT')");
    header("Location: dashboard_al.php?edificio=$meu_edificio&tab=contas");
    exit();
}

if (isset($_POST['add_iot'])) {
    $tipo = $_POST['iot_tipo'];
    $divisao = $_POST['iot_divisao'];
    $apartamento = $_POST['iot_apt'];
    $estado_inicial = ($tipo === 'Climatização' || $tipo === 'Piso Radiante') ? 'OFF (20)' : 'OFF';
    
    $stmt = $db->prepare("INSERT INTO equipamentos_iot (tipo, estado, id_edificio, id_apartamento, divisao) VALUES (:tipo, :estado, :edificio, :apt, :divisao)");
    $stmt->bindValue(':tipo', $tipo, SQLITE3_TEXT);
    $stmt->bindValue(':estado', $estado_inicial, SQLITE3_TEXT);
    $stmt->bindValue(':edificio', $meu_edificio, SQLITE3_TEXT);
    $stmt->bindValue(':apt', $apartamento, SQLITE3_TEXT);
    $stmt->bindValue(':divisao', $divisao, SQLITE3_TEXT);
    $stmt->execute();
    header("Location: dashboard_al.php?edificio=$meu_edificio&tab=apartamentos");
    exit();
}

if (isset($_GET['eliminar_iot'])) {
    $id_iot = $_GET['eliminar_iot'];
    $db->exec("DELETE FROM equipamentos_iot WHERE id_equipamento = $id_iot");
    header("Location: dashboard_al.php?edificio=$meu_edificio&tab=apartamentos");
    exit();
}

$moradores = $db->query("SELECT * FROM utilizadores WHERE id_unico LIKE '$meu_edificio%' AND tipo_acesso = 'CLT'");
$dispositivos = $db->query("SELECT * FROM equipamentos_iot WHERE id_edificio = '$meu_edificio' ORDER BY id_apartamento ASC");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão - Edifício <?php echo $meu_edificio; ?></title>
    <link rel="stylesheet" href="css/styles.css?v=1.1">
</head>
<body>

    <div class="app-wrapper">
        
        <aside class="sidebar">
            <div>
                <div class="sidebar-header">🏢 Edifício <?php echo $meu_edificio; ?></div>
                <ul class="sidebar-menu">
                    <li class="sidebar-item <?php echo ($tab_ativa === 'apartamentos') ? 'active' : ''; ?>">
                        <a href="dashboard_al.php?edificio=<?php echo $meu_edificio; ?>&tab=apartamentos">📊 Apartamentos & IoT</a>
                    </li>
                    <li class="sidebar-item <?php echo ($tab_ativa === 'contas') ? 'active' : ''; ?>">
                        <a href="dashboard_al.php?edificio=<?php echo $meu_edificio; ?>&tab=contas">👥 Gestão de Contas</a>
                    </li>
                </ul>
            </div>
            <div class="sidebar-footer">
                <div>User: <strong><?php echo $user_id; ?></strong></div>
                <a href="alterar_password.php" class="btn-sidebar-action">🔑 Alterar Password</a>
                <a href="index.html" class="btn-sidebar-action btn-sidebar-logout">🚪 Sair</a>
            </div>
        </aside>

        <div class="main-content">
            <header class="top-bar">
                <div>
                    <?php if ($user_tipo === 'AG'): ?>
                        <a href="dashboard_ag.php" class="btn-action" style="background:#64748b; color:white; text-decoration:none;">← Painel Geral AG</a>
                    <?php else: ?>
                        <span style="color:#64748b; font-weight:500;">Gestão Inteligente Local</span>
                    <?php endif; ?>
                </div>
            </header>

            <main class="container">
                <?php if ($tab_ativa === 'apartamentos'): ?>
                    <h2>Controlo de Infraestrutura IoT</h2>
                    <div class="grid-layout">
                        <div class="card" style="flex: 1 1 350px;">
                            <h3 class="card-title">Adicionar Dispositivo IoT</h3>
                            <form method="POST" action="dashboard_al.php?edificio=<?php echo $meu_edificio; ?>&tab=apartamentos">
                                <div class="form-group">
                                    <label>Fração / Apartamento:</label>
                                    <select name="iot_apt">
                                        <?php for($i=1; $i<=10; $i++) echo "<option value='A$i'>Apartamento A$i</option>"; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Divisão:</label>
                                    <select id="iot_divisao" name="iot_divisao" onchange="atualizarAparelhos()">
                                        <option value="Sala">Sala de Estar</option>
                                        <option value="Cozinha">Cozinha</option>
                                        <option value="Quarto">Quarto</option>
                                        <option value="WC">Casa de Banho (WC)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Tipo de Aparelho:</label>
                                    <select id="iot_tipo" name="iot_tipo"></select>
                                </div>
                                <button type="submit" name="add_iot" class="btn-submit" style="background:#10b981;">Instalar Aparelho</button>
                            </form>
                        </div>

                        <div class="card" style="flex: 1 1 600px;">
                            <h3 class="card-title">Aparelhos Instalados</h3>
                            <div class="table-responsive">
                                <table>
                                    <thead><tr><th>Apt</th><th>Divisão</th><th>Aparelho</th><th>Estado</th><th></th></tr></thead>
                                    <tbody>
                                        <?php while($row = $dispositivos->fetchArray(SQLITE3_ASSOC)): ?>
                                            <tr>
                                                <td><strong><?php echo $row['id_apartamento']; ?></strong></td>
                                                <td><span style="background:#e2e8f0; padding:3px 8px; border-radius:10px; font-size:0.85rem;"><?php echo $row['divisao']; ?></span></td>
                                                <td><?php echo $row['tipo']; ?></td>
                                                <td><strong><?php echo $row['estado']; ?></strong></td>
                                                <td>
                                                    <a href="dashboard_clt.php?simular_apt=<?php echo $row['id_apartamento']; ?>&simular_edf=<?php echo $meu_edificio; ?>" style="color:#3b82f6; text-decoration:none; font-weight:bold; margin-right:10px;">[Controlar]</a>
                                                    <a href="dashboard_al.php?edificio=<?php echo $meu_edificio; ?>&tab=apartamentos&eliminar_iot=<?php echo $row['id_equipamento']; ?>" onclick="return confirm('Eliminar dispositivo?')" style="color:#ef4444; text-decoration:none;">[Apagar]</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($tab_ativa === 'contas'): ?>
                    <h2>Controlo de Acessos de Moradores</h2>
                    <div class="grid-layout">
                        <div class="card" style="flex: 1 1 350px;">
                            <h3 class="card-title">Registar Novo Morador (CLT)</h3>
                            <form method="POST" action="dashboard_al.php?edificio=<?php echo $meu_edificio; ?>&tab=contas">
                                <div class="form-group">
                                    <label>ID Cliente:</label>
                                    <input type="text" name="clt_id" placeholder="Ex: <?php echo $meu_edificio; ?>A1CLT1" required>
                                </div>
                                <div class="form-group">
                                    <label>Password:</label>
                                    <input type="text" name="clt_pass" value="1234" required>
                                </div>
                                <button type="submit" name="criar_clt" class="btn-submit">Criar Conta Morador</button>
                            </form>
                        </div>

                        <div class="card" style="flex: 1 1 500px;">
                            <h3 class="card-title">Clientes Ativos</h3>
                            <div class="table-responsive">
                                <table>
                                    <thead><tr><th>ID Único Cliente</th><th>Ações</th></tr></thead>
                                    <tbody>
                                        <?php while($row = $moradores->fetchArray(SQLITE3_ASSOC)): ?>
                                            <tr>
                                                <td><strong><?php echo $row['id_unico']; ?></strong></td>
                                                <td><a href="dashboard_al.php?edificio=<?php echo $meu_edificio; ?>&tab=contas&eliminar_clt=<?php echo $row['id_unico']; ?>" onclick="return confirm('Remover morador?')" style="color:#ef4444; text-decoration:none; font-weight:bold;">[Apagar Conta]</a></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
    function atualizarAparelhos() {
        const divisao = document.getElementById('iot_divisao').value;
        const selectTipo = document.getElementById('iot_tipo');
        if(!selectTipo) return;
        selectTipo.innerHTML = "";
        let opcoes = [];
        if (divisao === "Sala" || divisao === "Quarto") opcoes = ["Iluminação", "Climatização", "Estores"];
        else if (divisao === "Cozinha") opcoes = ["Iluminação", "Forno", "Exaustor"];
        else if (divisao === "WC") opcoes = ["Iluminação", "Piso Radiante"];
        opcoes.forEach(function(aparelho) {
            let opt = document.createElement('option');
            opt.value = aparelho; opt.innerHTML = aparelho; selectTipo.appendChild(opt);
        });
    }
    if(document.getElementById('iot_divisao')) atualizarAparelhos();
    </script>
</body>
</html>
<?php $db->close(); ?>