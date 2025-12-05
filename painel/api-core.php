<?php
/**
 * Server Validator Script
 * Recebe dados do client-tracker.js e decide se aprova ou bloqueia.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite requisições de qualquer domínio (ajuste para produção)
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Responde rápido para requisições OPTIONS (Preflight do navegador)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Apenas aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Lê o JSON recebido
$input = file_get_contents('php://input');
// Log raw input for debugging
file_put_contents(__DIR__ . '/raw_input.txt', $input . "\n", FILE_APPEND);

$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// --- Lógica de Pontuação (Scoring) ---
$score = 0;
$reasons = [];

// Detecta Mobile via UA (Backup se o JS falhar)
$ua = $data['env']['ua'] ?? '';
$isMobileUA = (stripos($ua, 'Mobile') !== false || stripos($ua, 'Android') !== false || stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false);
$isMobileJS = $data['env']['touchSupport'] ?? false;
$isMobile = $isMobileUA || $isMobileJS;

// 1. Verifica Webdriver (Automação)
if (!empty($data['env']['webdriver'])) {
    $score += 100;
    $reasons[] = 'Webdriver detected';
}

// 2. Verifica User-Agent (Básico)
if (stripos($ua, 'Headless') !== false || stripos($ua, 'bot') !== false || stripos($ua, 'crawl') !== false) {
    $score += 100;
    $reasons[] = 'Bot UA detected';
}

// 3. Verifica Plugins (Browsers reais geralmente têm plugins, mas mobile muitas vezes não reporta)
$plugins = $data['env']['pluginsLength'] ?? 0;
if ($plugins === 0 && !$isMobile) { // Só penaliza se NÃO for mobile
    $score += 20;
    $reasons[] = 'No plugins found';
}

// 4. Verifica Resolução de Tela (Telas muito pequenas ou zeradas)
$width = $data['env']['screen']['width'] ?? 0;
$height = $data['env']['screen']['height'] ?? 0;
if (($width < 100 || $height < 100) && !$isMobile) { // Só penaliza se NÃO for mobile
    $score += 20; 
    $reasons[] = 'Invalid screen resolution';
}

// 5. Verifica WebGL (Headless muitas vezes não tem WebGL)
if (($data['fingerprint']['webgl'] ?? '') === 'no_webgl' && !$isMobile) { // Só penaliza se NÃO for mobile
    $score += 30;
    $reasons[] = 'No WebGL support';
}

// 6. Verifica Comportamento (Se foi muito rápido ou sem interação)
$duration = $data['collectDuration'] ?? 0;
$mouseMoves = $data['behavior']['mouseMoves'] ?? 0;
$clicks = $data['behavior']['clicks'] ?? 0;
$scrolls = $data['behavior']['scrolls'] ?? 0;
$keys = $data['behavior']['keys'] ?? 0;

if ($duration < 100 && !$isMobile) { // Só penaliza se NÃO for mobile
    $score += 40;
    $reasons[] = 'Too fast (script execution)';
}

if ($mouseMoves === 0 && $clicks === 0 && $scrolls === 0 && $keys === 0) {
    // Se for desktop e não tiver mouse, é suspeito.
    if (!$isMobile) {
        $score += 30;
        $reasons[] = 'No interaction (desktop)';
    }
}

// --- Decisão Final ---
// Limite de corte: 50 pontos
// --- Decisão Final ---
// MODO DE EMERGÊNCIA: SEMPRE APROVAR (Apenas logar o score)
$action = 'allow'; // ($score >= 50) ? 'block' : 'allow';

// Log (Salva em arquivo para debug e para o Dashboard)
$logFile = __DIR__ . '/cloaker_log.txt';
$logData = [
    'time' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'],
    'score' => $score,
    'action' => $action,
    'reasons' => $reasons,
    'ua' => $ua,
    // Tenta obter o país via headers do Cloudflare ou GeoIP padrão
    'country' => $_SERVER["HTTP_CF_IPCOUNTRY"] ?? $_SERVER["GEOIP_COUNTRY_CODE"] ?? 'Unknown'
];

// Garante UTF-8 para evitar erro no json_encode
array_walk_recursive($logData, function(&$item, $key) {
    if (is_string($item)) {
        $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
    }
});

$jsonLine = json_encode($logData);
if ($jsonLine === false) {
    $jsonLine = json_encode(['error' => 'JSON Encode Failed: ' . json_last_error_msg()]);
}

// Debug: Salva raw input se falhar
if (!file_put_contents($logFile, $jsonLine . "\n", FILE_APPEND | LOCK_EX)) {
    error_log("Falha ao escrever no log: $logFile");
}

// Resposta para o Cliente
echo json_encode([
    'status' => 'ok',
    'action' => $action,
    'score' => $score,
    // 'reasons' => $reasons // Descomente para debug no console do navegador
]);
