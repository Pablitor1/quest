<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Logs View</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f0f0f0; }
        .box { background: white; padding: 15px; border: 1px solid #ccc; margin-bottom: 20px; border-radius: 5px; }
        h2 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        pre { white-space: pre-wrap; word-wrap: break-word; background: #222; color: #0f0; padding: 10px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔍 Debug View</h1>

    <div class="box">
        <h2>📂 Conteúdo de cloaker_log.txt</h2>
        <?php
        $logFile = __DIR__ . '/cloaker_log.txt';
        if (file_exists($logFile)) {
            echo "<p>Tamanho: " . filesize($logFile) . " bytes</p>";
            echo "<pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
        } else {
            echo "<p style='color:red'>Arquivo não encontrado.</p>";
        }
        ?>
    </div>

    <div class="box">
        <h2>📥 Conteúdo de raw_input.txt (Dados recebidos do JS)</h2>
        <?php
        $rawFile = __DIR__ . '/raw_input.txt';
        if (file_exists($rawFile)) {
            echo "<p>Tamanho: " . filesize($rawFile) . " bytes</p>";
            echo "<pre>" . htmlspecialchars(file_get_contents($rawFile)) . "</pre>";
        } else {
            echo "<p style='color:orange'>Arquivo não encontrado (Nenhuma requisição chegou ainda ou permissão negada).</p>";
        }
        ?>
    </div>

    <div class="box">
        <h2>🛠 Verificação de Arquivos</h2>
        <ul>
            <li>api-core.php: <?php echo file_exists(__DIR__ . '/api-core.php') ? '✅ Existe' : '❌ Faltando'; ?></li>
            <li>admin-dashboard.php: <?php echo file_exists(__DIR__ . '/admin-dashboard.php') ? '✅ Existe' : '❌ Faltando'; ?></li>
        </ul>
    </div>
</body>
</html>
