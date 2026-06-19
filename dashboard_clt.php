<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$db = new SQLite3('database.db');
$user_id = $_SESSION['user_id'];
$user_tipo = $_SESSION['user_tipo'];

if (($user_tipo === 'AG' || $user_tipo === 'AL') && isset($_GET['simular_apt']) && isset($_GET['simular_edf'])) {
    $meu_edificio = strtoupper(trim($_GET['simular_edf']));
    $meu_apartamento = strtoupper(trim($_GET['simular_apt']));

    // Um AL só pode simular apartamentos dentro do seu próprio edifício
    if ($user_tipo === 'AL') {
        preg_match('/^(E\d+)/i', $user_id, $matches_al);
        $edificio_al = isset($matches_al[1]) ? strtoupper($matches_al[1]) : '';
        if ($meu_edificio !== $edificio_al) {
            header("Location: dashboard_al.php");
            exit();
        }
    }
    $modo_simulacao = true;
} else {
    $modo_simulacao = false;
    preg_match('/^(E\d+)(A\d+)/i', $user_id, $matches);
    $meu_edificio = isset($matches[1]) ? strtoupper($matches[1]) : 'E1';
    $meu_apartamento = isset($matches[2]) ? strtoupper($matches[2]) : 'A1';
}


$divisao_ativa = isset($_GET['divisao']) ? $_GET['divisao'] : 'Sala';

if (isset($_GET['acao']) && isset($_GET['id_iot'])) {
    $id_iot = $_GET['id_iot'];
    $acao = $_GET['acao'];

    // Confirma que o dispositivo pertence mesmo a este edifício/apartamento
    // antes de permitir qualquer alteração (evita controlar dispositivos de outros)
    $stmt_check = $db->prepare("SELECT estado FROM equipamentos_iot WHERE id_equipamento = :id AND id_edificio = :edf AND id_apartamento = :apt");
    $stmt_check->bindValue(':id', $id_iot, SQLITE3_INTEGER);
    $stmt_check->bindValue(':edf', $meu_edificio, SQLITE3_TEXT);
    $stmt_check->bindValue(':apt', $meu_apartamento, SQLITE3_TEXT);
    $dispositivo = $stmt_check->execute()->fetchArray(SQLITE3_ASSOC);

    if ($dispositivo) {
        $estado_atual = trim($dispositivo['estado']);
        $novo_estado = $estado_atual;

        if ($acao === 'alternar') {
            if ($estado_atual === 'ON') { $novo_estado = 'OFF'; }
            elseif ($estado_atual === 'OFF') { $novo_estado = 'ON'; }
            else {
                if (strpos($estado_atual, 'OFF') !== false) { $novo_estado = str_replace('OFF', 'ON', $estado_atual); }
                else { $novo_estado = str_replace('ON', 'OFF', $estado_atual); }
            }
        } elseif ($acao === 'subir_temp' || $acao === 'descer_temp') {
            preg_match('/\d+(\.\d+)?/', $estado_atual, $num);
            $num_atual = isset($num[0]) ? floatval($num[0]) : 20.0;
            $novo_num = ($acao === 'subir_temp') ? ($num_atual + 0.5) : ($num_atual - 0.5);
            if (strpos($estado_atual, 'OFF') !== false) { $novo_estado = "OFF ($novo_num)"; }
            else { $novo_estado = "ON ($novo_num)"; }
        }

        $stmt_update = $db->prepare("UPDATE equipamentos_iot SET estado = :estado WHERE id_equipamento = :id");
        $stmt_update->bindValue(':estado', $novo_estado, SQLITE3_TEXT);
        $stmt_update->bindValue(':id', $id_iot, SQLITE3_INTEGER);
        $stmt_update->execute();
    }

    $redir = "dashboard_clt.php?divisao=" . $divisao_ativa;
    if ($modo_simulacao) { $redir .= "&simular_apt=$meu_apartamento&simular_edf=$meu_edificio"; }
    header("Location: " . $redir);
    exit();
}

$stmt_dispositivos = $db->prepare("SELECT * FROM equipamentos_iot WHERE id_edificio = :edf AND id_apartamento = :apt AND divisao = :div");
$stmt_dispositivos->bindValue(':edf', $meu_edificio, SQLITE3_TEXT);
$stmt_dispositivos->bindValue(':apt', $meu_apartamento, SQLITE3_TEXT);
$stmt_dispositivos->bindValue(':div', $divisao_ativa, SQLITE3_TEXT);
$res_dispositivos = $stmt_dispositivos->execute();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Domótico - Apartamento <?php echo $meu_apartamento; ?></title>
    <link rel="stylesheet" href="css/styles.css?v=1.2">
</head>
<body>
    <div class="app-wrapper">
        
        <?php 
            $url_base = "dashboard_clt.php?divisao=X";
            if($modo_simulacao) $url_base = "dashboard_clt.php?simular_apt=$meu_apartamento&simular_edf=$meu_edificio&divisao=X";
        ?>
        <aside class="sidebar">
            <div>
                <div class="sidebar-header">🏠 Apartamento <?php echo $meu_apartamento; ?></div>
                <ul class="sidebar-menu">
                    <li class="sidebar-item <?php echo ($divisao_ativa === 'Sala') ? 'active' : ''; ?>">
                        <a href="<?php echo str_replace('X', 'Sala', $url_base); ?>">🛋️ Sala de Estar</a>
                    </li>
                    <li class="sidebar-item <?php echo ($divisao_ativa === 'Cozinha') ? 'active' : ''; ?>">
                        <a href="<?php echo str_replace('X', 'Cozinha', $url_base); ?>">🍳 Cozinha</a>
                    </li>
                    <li class="sidebar-item <?php echo ($divisao_ativa === 'Quarto') ? 'active' : ''; ?>">
                        <a href="<?php echo str_replace('X', 'Quarto', $url_base); ?>">🛏️ Quarto Principal</a>
                    </li>
                    <li class="sidebar-item <?php echo ($divisao_ativa === 'WC') ? 'active' : ''; ?>">
                        <a href="<?php echo str_replace('X', 'WC', $url_base); ?>">🚿 Casa de Banho (WC)</a>
                    </li>
                </ul>
            </div>
            <div class="sidebar-footer">
                <div>Prédio: <strong><?php echo $meu_edificio; ?></strong></div>
                <div>Apartamento: <strong><?php echo $meu_apartamento; ?></strong></div>
                <?php if (!$modo_simulacao): ?>
                    <a href="alterar_password.php" class="btn-sidebar-action" style="margin-top:10px;">🔑 Alterar Password</a>
                <?php endif; ?>
                <a href="index.html" class="btn-sidebar-action btn-sidebar-logout">🚪 Sair</a>
            </div>
        </aside>

        <div class="main-content">
            <header class="top-bar">
                <div>
                    <?php if ($modo_simulacao): ?>
                        <a href="dashboard_al.php?edificio=<?php echo $meu_edificio; ?>" class="btn-action">← Voltar ao Edifício</a>
                    <?php else: ?>
                        <span style="color:#64748b; font-weight:600;">Painel de Controlo Domótico</span>
                    <?php endif; ?>
                </div>
            </header>

            <main class="container">
                <h2>Ambiente: <span style="color:#3b82f6;"><?php echo $divisao_ativa; ?></span></h2>
                <p style="color:#010101; margin-bottom: 2rem;">Controlo local de atuadores.</p>
                
                <div class="grid-layout">
                    <?php 
                    $has_items = false;
                    while($row = $res_dispositivos->fetchArray(SQLITE3_ASSOC)): 
                        $has_items = true;
                        $id = $row['id_equipamento']; $estado = $row['estado']; $tipo = $row['tipo'];
                        $is_on = (strpos($estado, 'ON') !== false);
                        $url_acao = "dashboard_clt.php?divisao=$divisao_ativa&id_iot=$id&";
                        if($modo_simulacao) $url_acao = "dashboard_clt.php?simular_apt=$meu_apartamento&simular_edf=$meu_edificio&divisao=$divisao_ativa&id_iot=$id&";
                    ?>
                        <div class="card" style="border-top-color: <?php echo $is_on ? '#10b981' : '#ef4444'; ?>;">
                            <h4 class="card-title">
                                <?php echo ($tipo === 'Iluminação') ? '💡 Iluminação' : (($tipo === 'Climatização') ? '❄️ Climatização' : (($tipo === 'Estores') ? '🪟 Estores' : '🔌 ' . $tipo)); ?>
                            </h4>
                            <div style="display:flex; flex-direction:column; gap:12px; margin-top:1rem;">
                                
                                <?php if ($tipo === 'Climatização' || $tipo === 'Piso Radiante'): ?>
                                    <div style="display:flex; justify-content:space-between; align-items:center;">
                                        <span>Alimentação: <strong style="color:<?php echo $is_on ? '#10b981':'#ef4444';?>;"><?php echo $is_on ? 'LIGADO':'DESLIGADO';?></strong></span>
                                        <a href="<?php echo $url_acao; ?>acao=alternar&estado_atual=<?php echo $estado; ?>" class="btn-action" style="background:<?php echo $is_on ? '#ef4444':'#10b981';?>; color:white; padding: 4px 10px; text-decoration:none; border:none;">
                                            <?php echo $is_on ? 'Desligar':'Ligar'; ?>
                                        </a>
                                    </div>
                                    <?php if($is_on): 
                                        preg_match('/\d+(\.\d+)?/', $estado, $num);
                                        $graus = isset($num[0]) ? $num[0] : '20';
                                    ?>
                                        <div style="display:flex; justify-content:space-between; align-items:center; background:#f1f5f9; padding:8px; border-radius:6px;">
                                            <span style="font-size:1.1rem; color:#0f172a;">Termostato: <strong><?php echo $graus; ?> °C</strong></span>
                                            <div>
                                                <a href="<?php echo $url_acao; ?>acao=descer_temp&estado_atual=<?php echo $estado; ?>" class="btn-action" style="text-decoration:none; font-weight:bold;">-</a>
                                                <a href="<?php echo $url_acao; ?>acao=subir_temp&estado_atual=<?php echo $estado; ?>" class="btn-action" style="text-decoration:none; font-weight:bold;">+</a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                
                                <?php else: ?>
                                    <div style="display:flex; justify-content:space-between; align-items:center;">
                                        <span>Estado: <strong style="color:<?php echo ($estado === 'ON') ? '#10b981' : '#ef4444'; ?>;"><?php echo $estado; ?></strong></span>
                                        <a href="<?php echo $url_acao; ?>acao=alternar&estado_atual=<?php echo $estado; ?>" class="btn-submit" style="width:auto; padding:6px 14px; text-decoration:none; background:<?php echo ($estado === 'ON') ? '#ef4444' : '#10b981'; ?>;">
                                            <?php echo ($estado === 'ON') ? 'Desligar' : 'Ligar';
                                            ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <?php if(!$has_items): ?>
                        <div class="card" style="flex:1 1 100%; text-align:center; color:#64748b; border-top-color:#cbd5e1;">Nenhum aparelho configurado para a divisão <strong><?php echo $divisao_ativa; ?></strong>.</div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
<?php $db->close(); ?>