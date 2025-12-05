<?php
/**
 * Admin Dashboard - Cloaker System
 * Visualiza os logs gerados pelo server-validator.php
 */

session_start();

// Configuração
$PASSWORD = 'admin123'; // Mude isso para uma senha segura!
$LOG_FILE = __DIR__ . '/cloaker_log.txt';

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin-dashboard.php');
    exit;
}

// Login Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['password']) && $_POST['password'] === $PASSWORD) {
        $_SESSION['logged_in'] = true;
    } else {
        $error = "Senha incorreta!";
    }
}

// Check Auth
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Login - Dashboard</title>
        <style>
            body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f2f5; }
            .login-box { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            input { padding: 8px; margin-bottom: 10px; width: 100%; box-sizing: border-box; }
            button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
            button:hover { background: #0056b3; }
            .error { color: red; margin-bottom: 10px; font-size: 0.9rem; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>🔒 Acesso Restrito</h2>
            <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Senha" required>
                <button type="submit">Entrar</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Read Logs
$logs = [];
if (file_exists($LOG_FILE)) {
    $lines = file($LOG_FILE);
    foreach ($lines as $line) {
        $data = json_decode($line, true);
        if ($data) {
            // Adiciona no início para mostrar mais recente primeiro
            array_unshift($logs, $data);
        }
    }
}

// Stats
$total = count($logs);
$blocked = 0;
$allowed = 0;
foreach ($logs as $log) {
    if ($log['action'] === 'block') $blocked++;
    else $allowed++;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Cloaker</title>
    <style>
        :root { --bg: #f8f9fa; --card-bg: #ffffff; --text: #333; --border: #dee2e6; --green: #28a745; --red: #dc3545; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: var(--card-bg); padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid var(--border); }
        .card h3 { margin: 0 0 10px 0; font-size: 0.9rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
        .card .value { font-size: 2rem; font-weight: bold; }
        .value.green { color: var(--green); }
        .value.red { color: var(--red); }
        
        table { width: 100%; border-collapse: collapse; background: var(--card-bg); border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #f1f3f5; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; color: #555; }
        tr:last-child td { border-bottom: none; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .badge.allow { background: #d4edda; color: #155724; }
        .badge.block { background: #f8d7da; color: #721c24; }
        
        .reasons { font-size: 0.85rem; color: #666; }
        .logout { color: #dc3545; text-decoration: none; font-weight: 500; border: 1px solid #dc3545; padding: 6px 12px; border-radius: 4px; transition: all 0.2s; }
        .logout:hover { background: #dc3545; color: white; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🕵️‍♂️ Cloaker Dashboard</h1>
        <a href="?logout=1" class="logout">Sair</a>
    </div>

    <div class="stats">
        <div class="card">
            <h3>Total Acessos</h3>
            <div class="value"><?php echo $total; ?></div>
        </div>
        <div class="card">
            <h3>Aprovados</h3>
            <div class="value green"><?php echo $allowed; ?></div>
        </div>
        <div class="card">
            <h3>Bloqueados</h3>
            <div class="value red"><?php echo $blocked; ?></div>
        </div>
    </div>

    <div class="card" style="padding: 0; border: none;">
        <table>
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>IP</th>
                    <th>País</th>
                    <th>Dispositivo</th>
                    <th>Status</th>
                    <th>Score</th>
                    <th>Motivos / Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 20px;">Nenhum registro encontrado. Verifique as permissões de escrita no arquivo cloaker_log.txt</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <?php 
                            // Parse simples de UA
                            $ua = $log['ua'];
                            $device = 'Desktop';
                            if (stripos($ua, 'Mobile') !== false || stripos($ua, 'Android') !== false || stripos($ua, 'iPhone') !== false) {
                                $device = 'Mobile';
                            }
                            
                            $browser = 'Outro';
                            if (stripos($ua, 'Chrome') !== false) $browser = 'Chrome';
                            elseif (stripos($ua, 'Safari') !== false) $browser = 'Safari';
                            elseif (stripos($ua, 'Firefox') !== false) $browser = 'Firefox';
                            elseif (stripos($ua, 'Edge') !== false) $browser = 'Edge';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['time']); ?></td>
                            <td><?php echo htmlspecialchars($log['ip']); ?></td>
                            <td><strong><?php echo htmlspecialchars($log['country'] ?? '-'); ?></strong></td>
                            <td><?php echo $device . ' / ' . $browser; ?></td>
                            <td>
                                <span class="badge <?php echo $log['action']; ?>">
                                    <?php echo $log['action'] === 'allow' ? 'APROVADO' : 'BLOQUEADO'; ?>
                                </span>
                            </td>
                            <td><strong><?php echo $log['score']; ?></strong></td>
                            <td class="reasons">
                                <?php 
                                if (!empty($log['reasons'])) {
                                    echo implode(', ', array_map('htmlspecialchars', $log['reasons']));
                                } else {
                                    echo '<span style="color:#ccc">-</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
