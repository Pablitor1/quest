// ===============================
// Tracker Supremo v2 - Client Side
// ===============================

// Endpoint oficial (PHP v2 dentro de /painel/)
const TRACKER_ENDPOINT = 'https://lojaphamelagourmet.site/painel/tracker-supremee-v2.php';

(function(){

    const startCollect = performance.now();

    // ID de sessão persistente no navegador
    function getOrCreateSessionId() {
        try {
            const KEY = '_supreme_id';
            let id = localStorage.getItem(KEY);
            if (!id) {
                id = 'sess-' + Date.now() + '-' + Math.random().toString(36).slice(2,9);
                localStorage.setItem(KEY, id);
            }
            return id;
        } catch(e) {
            return 'sess-tmp-' + Date.now() + '-' + Math.random().toString(36).slice(2,9);
        }
    }

    const sessionId = getOrCreateSessionId();

    // --- Coletas de ambiente/hardware ---
    function getPluginsCount() {
        try {
            if (navigator.plugins) {
                return navigator.plugins.length || 0;
            }
        } catch(e){}
        return 0;
    }

    // placeholder simples; se quiser melhorar depois dá pra detectar fontes reais
    function getFontsCountQuickGuess() {
        return 0;
    }

    function getLanguages() {
        let langs = [];
        if (navigator.languages && navigator.languages.length) {
            langs = navigator.languages.slice(0,5);
        } else if (navigator.language) {
            langs = [navigator.language];
        }
        return langs;
    }

    function getTimezone() {
        try {
            return Intl.DateTimeFormat().resolvedOptions().timeZone || '';
        } catch(e) {
            return '';
        }
    }

    function getScreenInfo() {
        try {
            return window.screen.width + 'x' + window.screen.height;
        } catch(e) {
            return '';
        }
    }

    function hasTouchSupport() {
        try {
            return (
                'ontouchstart' in window ||
                (navigator.maxTouchPoints && navigator.maxTouchPoints > 0) ||
                (navigator.msMaxTouchPoints && navigator.msMaxTouchPoints > 0)
            );
        } catch(e){
            return false;
        }
    }

    const webdriver = !!navigator.webdriver;

    // --- Timings / jitter ---
    const timingData = {
        raf: [],
        timeout: []
    };

    (function collectRAF(){
        const times = [];
        let last = performance.now();
        let count = 0;
        function loop(now){
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

    (function collectTimeout(){
        const times = [];
        let lastTO = performance.now();
        let count = 0;
        function runTO(){
            const now = performance.now();
            const diff = now - lastTO;
            lastTO = now;
            if (count > 0) times.push(diff);
            count++;
            if (count < 15) {
                setTimeout(runTO, 0);
            } else {
                timingData.timeout = times;
            }
        }
        setTimeout(runTO, 0);
    })();

    // --- Interação humana ---
    const behavior = {
        mv: 0,      // mousemove
        clicks: 0,  // click
        keys: 0,    // keydown
        scroll: 0   // scroll
    };

    window.addEventListener('mousemove', () => { behavior.mv++; }, {passive:true});
    window.addEventListener('click',     () => { behavior.clicks++; }, {passive:true});
    window.addEventListener('keydown',   () => { behavior.keys++; }, {passive:true});
    window.addEventListener('scroll',    () => { behavior.scroll++; }, {passive:true});

    // --- Fingerprints leves ---
    function simpleHash(str){
        let h = 0, i, chr;
        for (i = 0; i < str.length; i++) {
            chr = str.charCodeAt(i);
            h = ((h << 5) - h) + chr;
            h |= 0;
        }
        return 'h'+(h>>>0);
    }

    function getCanvasSignature() {
        try {
            const c = document.createElement('canvas');
            c.width = 200;
            c.height = 40;
            const ctx = c.getContext('2d');

            ctx.textBaseline = "top";
            ctx.font = "14px 'Arial'";
            ctx.textBaseline = "alphabetic";
            ctx.fillStyle = "#f60";
            ctx.fillRect(0,0,200,40);
            ctx.fillStyle = "#069";
            ctx.fillText("tracker_supremo", 2, 20);

            const data = c.toDataURL();
            return simpleHash(data);
        } catch(e){
            return 'canvas_err:' + (''+e).slice(0,60);
        }
    }

    function getWebGLSignature() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            if (!gl) return 'no_webgl';

            const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
            const vendor = debugInfo ? gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL) : '';
            const renderer = debugInfo ? gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) : '';

            return simpleHash(vendor + '|' + renderer);
        } catch(e){
            return 'no_webgl';
        }
    }

    function getAudioSignature() {
        try {
            const AC = window.AudioContext || window.webkitAudioContext;
            if (!AC) return 'no_audio';
            const ctx = new AC();
            ctx.close && ctx.close();
            return 'ok_audio';
        } catch(e){
            return 'no_audio';
        }
    }

    // --- Envio pro servidor v2 ---
    function sendData() {
        const totalCollectMs = performance.now() - startCollect;

        const payload = {
            id: sessionId,

            // ambiente / fingerprint
            ua: navigator.userAgent || '',
            webdriver: webdriver,
            hardwareConcurrency: navigator.hardwareConcurrency || null,
            headless: {
                plugins: getPluginsCount()
            },
            fonts_count: getFontsCountQuickGuess(),
            languages: getLanguages(),
            timezone: getTimezone(),
            screen: getScreenInfo(),
            hasTouch: hasTouchSupport(),

            canvas: getCanvasSignature(),
            webgl: getWebGLSignature(),
            audio: getAudioSignature(),

            timing: timingData,
            total_collect_ms: totalCollectMs,

            behavior: behavior,

            url: location.href,
            ref: document.referrer || '',

            collected_at: Math.floor(Date.now()/1000)
        };

        fetch(TRACKER_ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            keepalive: true,
            body: JSON.stringify(payload)
        }).catch(e => {
            // silencioso no cliente
        });
    }

    // manda 1 vez depois de ~1.2s (já tem jitter, behavior, etc)
    setTimeout(sendData, 1200);

    // ping contínuo pra manter last_seen quente
    setInterval(sendData, 5000);
window.addEventListener('beforeunload', () => {
  navigator.sendBeacon(TRACKER_ENDPOINT, JSON.stringify({
    id: sessionId,
    ua: navigator.userAgent || '',
    event: 'offline',
    collected_at: Math.floor(Date.now()/1000)
  }));
});

})();
