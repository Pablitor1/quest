<?php
// Script de Diagnóstico de Permissões
header('Content-Type: text/html; charset=utf-8');

$logFile = __DIR__ . '/cloaker_log.txt';
$testContent = "TESTE DE ESCRITA: " . date('Y-m-d H:i:s') . "\n";

echo "<h1>Diagnóstico de Permissões</h1>";
echo "<p>Tentando escrever em: <strong>$logFile</strong></p>";

// Verifica se o arquivo existe
if (file_exists($logFile)) {
    echo "<p>✅ O arquivo existe.</p>";
    echo "<p>Permissões atuais: " . substr(sprintf('%o', fileperms($logFile)), -4) . "</p>";
    echo "<p>Dono do arquivo: " . fileowner($logFile) . "</p>";
} else {
    echo "<p>⚠️ O arquivo não existe. Tentando criar...</p>";
}

// Tenta escrever
if (file_put_contents($logFile, $testContent, FILE_APPEND)) {
    echo "<h2 style='color: green;'>SUCESSO! ✅</h2>";
    echo "<p>O PHP conseguiu escrever no arquivo. O problema não é permissão de arquivo.</p>";
    echo "<p>Verifique se o arquivo agora contém a linha de teste.</p>";
} else {
    echo "<h2 style='color: red;'>ERRO! ❌</h2>";
    echo "<p>O PHP NÃO conseguiu escrever no arquivo.</p>";
    echo "<p><strong>Possíveis causas:</strong></p>";
    echo "<ul>";
    echo "<li>Permissões da pasta incorretas (a pasta precisa de permissão de escrita).</li>";
    echo "<li>O usuário do PHP (geralmente www-data) não é o dono do arquivo.</li>";
    echo "<li>Disco cheio (improvável).</li>";
    echo "</ul>";
    
    $error = error_get_last();
    if ($error) {
        echo "<p><strong>Erro do Sistema:</strong> " . $error['message'] . "</p>";
    }
}

echo "<hr>";
echo "<h3>Informações do Ambiente:</h3>";
echo "<p>Usuário PHP: " . get_current_user() . "</p>";
echo "<p>Diretório Atual: " . __DIR__ . "</p>";
?>
