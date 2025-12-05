/**
 * Client Tracker Script
 * Coleta dados do navegador e comportamento do usuário para validação no servidor.
 */

(function () {
    // Configuração
    const VALIDATOR_ENDPOINT = 'server-validator.php'; // Ajuste para a URL real do seu servidor
    const COLLECT_DELAY_MS = 1500; // Tempo de espera antes de enviar (para coletar comportamento)

    const startCollect = performance.now();
    let hasSentData = false;

    // --- 1. Identificação de Sessão ---
    function getSessionId() {
        const KEY = '_cloaker_sid';
        let id = localStorage.getItem(KEY);
        if (!id) {
            id = 'sess-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem(KEY, id);
        }
        return id;
    }

    // --- 2. Coleta de Ambiente (Fingerprinting Leve) ---
    function getEnvironmentData() {
        return {
            ua: navigator.userAgent,
            webdriver: navigator.webdriver || false,
            language: navigator.language || 'unknown',
            languages: navigator.languages || [],
            platform: navigator.platform,
            hardwareConcurrency: navigator.hardwareConcurrency || 0,
            deviceMemory: navigator.deviceMemory || 0,
            screen: {
                width: window.screen.width,
                height: window.screen.height,
                availWidth: window.screen.availWidth,
                availHeight: window.screen.availHeight,
                colorDepth: window.screen.colorDepth,
            },
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            touchSupport: 'ontouchstart' in window || navigator.maxTouchPoints > 0,
            pluginsLength: navigator.plugins ? navigator.plugins.length : 0,
        };
    }

    // --- 3. Fingerprinting Avançado (Canvas/WebGL/Audio) ---
    function getCanvasFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = 200;
            canvas.height = 50;
            ctx.textBaseline = "top";
            ctx.font = "16px Arial";
            ctx.fillStyle = "#f60";
            ctx.fillRect(125, 1, 62, 20);
            ctx.fillStyle = "#069";
            ctx.fillText("Cloaker_FP_v1", 2, 15);
            ctx.fillStyle = "rgba(102, 204, 0, 0.7)";
            ctx.fillText("Cloaker_FP_v1", 4, 17);
            return canvas.toDataURL().slice(-50);
        } catch (e) {
            return 'error';
        }
    }

    function getWebGLFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            if (!gl) return 'no_webgl';
            const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
            const vendor = gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL);
            const renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);
            return { vendor, renderer };
        } catch (e) {
            return 'error';
        }
    }

    function getAudioFingerprint() {
        try {
            const AC = window.AudioContext || window.webkitAudioContext;
            if (!AC) return 'no_audio';
            const ctx = new AC();
            ctx.close && ctx.close();
            return 'ok_audio';
        } catch (e) {
            return 'no_audio';
        }
    }

    // --- 4. Coleta de Comportamento e Timing ---
    const behavior = {
        mouseMoves: 0,
        clicks: 0,
        scrolls: 0,
        keys: 0
    };

    const timingData = {
        raf: []
    };

    // Coleta Jitter (Variação de tempo entre frames) para detectar bots acelerados
    (function collectRAF() {
        const times = [];
        let last = performance.now();
        let count = 0;
        function loop(now) {
            const diff = now - last;
            last = now;
            if (count > 0) times.push(diff);
            count++;
            if (count < 15) {
                requestAnimationFrame(loop);
            } else {
                timingData.raf = times;
            }
        }
        requestAnimationFrame(loop);
    })();

    function trackEvents() {
        document.addEventListener('mousemove', () => behavior.mouseMoves++, { passive: true });
        document.addEventListener('click', () => behavior.clicks++, { passive: true });
        document.addEventListener('scroll', () => behavior.scrolls++, { passive: true });
        document.addEventListener('keydown', () => behavior.keys++, { passive: true });
    }

    // --- 5. Envio de Dados ---
    function sendData() {
        if (hasSentData) return;
        hasSentData = true;

        const payload = {
            sessionId: getSessionId(),
            url: window.location.href,
            referrer: document.referrer,
            timestamp: Date.now(),
            collectDuration: performance.now() - startCollect,
            env: getEnvironmentData(),
            fingerprint: {
                canvas: getCanvasFingerprint(),
                webgl: getWebGLFingerprint(),
                audio: getAudioFingerprint()
            },
            behavior: behavior,
            timing: timingData
        };

        // Envia via POST
        fetch(VALIDATOR_ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(res => res.json())
            .then(response => {
                console.log('Cloaker Response:', response);
                if (response.action === 'block') {
                    // AÇÃO DE BLOQUEIO: Redirecionar para página segura ou mostrar erro
                    window.location.href = 'https://google.com'; // Exemplo: manda pro Google
                } else if (response.action === 'allow') {
                    // AÇÃO DE APROVAÇÃO: Não faz nada, ou libera conteúdo oculto
                    console.log('User allowed.');
                    if (window.onCloakerPass) window.onCloakerPass(); // Callback opcional
                }
            })
            .catch(err => {
                console.error('Cloaker Error:', err);
                // Em caso de erro de rede, decida se bloqueia ou libera (fail-open ou fail-closed)
            });
    }

    // Inicialização
    trackEvents();
    setTimeout(sendData, COLLECT_DELAY_MS);

})();
