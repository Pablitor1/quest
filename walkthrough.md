# Guia de Instalação - Sistema de Cloaker/Filtro

Este sistema replica a funcionalidade do "Tracker Supreme" para filtrar bots e auditores indesejados.

## Arquivos do Sistema

1.  **`client-tracker.js`**: Script que vai no seu site (Frontend).
2.  **`server-validator.php`**: Script que vai no seu servidor (Backend).

## Passo a Passo para Instalação

### 1. Configurar o Backend (Servidor)
1.  Tenha uma hospedagem ou VPS com suporte a **PHP**.
2.  Faça upload do arquivo `server-validator.php` para uma pasta pública (ex: `public_html/painel/`).
3.  Anote a URL completa, por exemplo: `https://seusite.com/painel/server-validator.php`.

### 2. Configurar o Frontend (Seu Site)
1.  Abra o arquivo `client-tracker.js`.
2.  Edite a linha 7 e coloque a URL do seu servidor:
    ```javascript
    const VALIDATOR_ENDPOINT = 'https://seusite.com/painel/server-validator.php';
    ```
3.  Adicione o script em todas as páginas que você quer proteger (Landing Page, Advertorial, etc). Coloque antes do fechamento da tag `</body>`:
    ```html
    <script src="caminho/para/client-tracker.js"></script>
    ```

### 3. Personalizar a Ação de Bloqueio
No arquivo `client-tracker.js` (linha 100), você define o que acontece quando um bot é detectado:

```javascript
if (response.action === 'block') {
    // Redireciona para o Google ou página de erro
    window.location.href = 'https://google.com';
}
```

## Como Testar
1.  Acesse sua página normalmente. Se nada acontecer, você foi **Aprovado**.
2.  Verifique o arquivo `cloaker_log.txt` no seu servidor para ver os logs de acesso e a pontuação de cada visita.
3.  Para testar o bloqueio, você pode tentar acessar usando um navegador em modo "Headless" ou alterar o User-Agent para "Googlebot".

## Segurança
O script PHP já contém regras para bloquear:
- Navegadores automatizados (Selenium/Puppeteer).
- Visitantes sem movimento de mouse/interação.
- Resoluções de tela suspeitas.
- User-Agents de bots conhecidos.

## 4. Painel Administrativo (Dashboard)
Para ver quem está acessando seu site e quem foi bloqueado:

1.  Suba o arquivo `admin-dashboard.php` para a mesma pasta do `server-validator.php`.
2.  Acesse pelo navegador: `https://seusite.com/painel/admin-dashboard.php`.
3.  A senha padrão é **`admin123`**.
    *   **Importante:** Abra o arquivo `admin-dashboard.php` e mude a variável `$PASSWORD` para uma senha segura!

O painel mostrará gráficos de acessos e uma tabela detalhada com cada visitante, incluindo o motivo do bloqueio (se houver).

## 5. Camuflagem e Segurança (Avançado)
Para deixar o script mais profissional e difícil de ler (assim como o do concorrente), use o arquivo **`tracker-secure.min.js`** em vez do `client-tracker.js`.

1.  Ele faz a mesma coisa, mas o código está "minificado" e ofuscado.
2.  Lembre-se de abrir o `tracker-secure.min.js` e mudar a URL `server-validator.php` para a sua URL real (está logo no começo do arquivo).

## 6. Dúvidas Comuns

### Isso substitui o Pixel do Facebook/TikTok?
**Não.** Esse sistema é um **Filtro de Segurança** (Cloaker).
*   **Função:** Impedir que robôs e auditores vejam sua página de vendas real (Black Hat).
*   **Pixel/UTM:** Você deve continuar usando seus scripts de Pixel e UTM normalmente. Esse script não interfere neles, ele apenas protege a página.

### Onde colocar os arquivos no cPanel?
1.  Vá no **Gerenciador de Arquivos** do cPanel.
2.  Na pasta `public_html`, crie uma pasta chamada `painel` (ou outro nome discreto como `analytics`, `secure`).
3.  Faça upload do `server-validator.php` e `admin-dashboard.php` para dentro dessa pasta.
4.  O arquivo JS (`tracker-secure.min.js`) pode ficar na raiz ou em qualquer pasta, desde que você aponte o link correto no HTML.

## 7. Página de Captcha (Pre-sell)
Se você quiser usar a página de "Captcha" igual à do concorrente como sua página inicial:

1.  Use o arquivo **`captcha.html`** que eu criei.
2.  Abra o arquivo e edite a linha 256:
    ```javascript
    window.location.href = "index.html"; // Mude para o link da sua página de vendas
    ```
3.  Essa página já vem com o `tracker-secure.min.js` embutido.

## 8. Proteção em Todo o Funnel (Importante)
Assim como o concorrente faz, você deve colocar o script **em todas as páginas** do seu funil (VSL, Checkout, Upsell, Obrigado).

**Por que?**
*   Se um robô tentar pular a primeira página e ir direto para o checkout, ele será pego.
*   Isso garante que você tenha o rastro completo do visitante.

**Como fazer:**
Cole este código antes do fechamento da tag `</body>` em **todas** as suas páginas HTML:

```html
<script src="tracker-secure.min.js" defer></script>
```
*(Certifique-se de que o arquivo .js está acessível naquele caminho)*



