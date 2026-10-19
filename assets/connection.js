/* Shared connection feedback. Never queue or automatically repeat customer actions. */
(() => {
    'use strict';
    const script = document.currentScript;
    const base = new URL('../', script.src);
    const nativeFetch = window.fetch.bind(window);
    let banner, message = '', checking = false, uncertain = false;
    const offlineMessage = 'You are offline. Please reconnect to the internet before continuing. Your action has not been sent.';
    const reachMessage = 'We cannot reach the system. Check your internet connection and try again.';
    const uncertainMessage = 'The connection was interrupted. We could not confirm whether your action completed. Reconnect and check the latest records before trying again.';

    function render() {
        if (!document.body) return;
        if (!banner) {
            const style = document.createElement('style');
            style.textContent = '#appConnectionWarning{position:fixed;inset:0 0 auto;z-index:20000;background:#fff3cd;color:#573d05;border-bottom:2px solid #e5af31;box-shadow:0 3px 12px #0002;padding:14px 20px;display:flex;gap:14px;align-items:center;justify-content:center;font:15px/1.5 Arial,sans-serif}#appConnectionWarning[hidden]{display:none}#appConnectionWarning p{margin:0;max-width:850px}#appConnectionWarning button{flex-shrink:0;background:#fff;border:1px solid #8a6208;border-radius:6px;color:#573d05;padding:7px 12px;cursor:pointer}@media print{#appConnectionWarning{display:none!important}}@media(max-width:600px){#appConnectionWarning{align-items:flex-start;flex-direction:column;gap:8px;padding:12px;font-size:14px}}';
            document.head.append(style);
            banner = document.createElement('aside'); banner.id = 'appConnectionWarning';
            banner.setAttribute('role', 'alert'); banner.setAttribute('aria-live', 'assertive');
            const text = document.createElement('p');
            const retry = document.createElement('button'); retry.type = 'button'; retry.textContent = 'Check connection';
            retry.addEventListener('click', checkConnection);
            banner.append(text, retry); document.body.append(banner);
        }
        banner.hidden = !message;
        banner.querySelector('p').textContent = message;
    }
    function warn(text) {
        message = uncertain && [offlineMessage, reachMessage].includes(text) ? uncertainMessage : text;
        render();
    }
    function isInternal(input) {
        try { return new URL(input instanceof Request ? input.url : input, location.href).origin === location.origin; }
        catch (error) { return false; }
    }
    async function checkConnection() {
        if (checking) return;
        if (navigator.onLine === false) { warn(offlineMessage); return; }
        checking = true;
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 8000);
        try {
            const response = await nativeFetch(new URL('connection-check.php', base), {cache:'no-store', signal:controller.signal});
            if (response.status !== 204 || response.headers.get('X-App-Connection') !== 'ok') throw new Error();
            // Leave an interrupted action warning visible: connectivity does not prove it saved.
            if (uncertain) warn('Connection restored. Please check the latest records before repeating the interrupted action.');
            else warn('');
        } catch (error) { warn(navigator.onLine === false ? offlineMessage : reachMessage); }
        finally { clearTimeout(timer); checking = false; }
    }
    window.fetch = async function (input, options) {
        if (!isInternal(input)) return nativeFetch(input, options);
        if (navigator.onLine === false) { warn(offlineMessage); throw new Error(offlineMessage); }
        const method = String(options?.method || (input instanceof Request ? input.method : 'GET')).toUpperCase();
        try { return await nativeFetch(input, options); }
        catch (error) {
            if (error.name === 'AbortError') throw error;
            const text = ['GET','HEAD','OPTIONS'].includes(method) ? reachMessage : uncertainMessage;
            if (text === uncertainMessage) uncertain = true;
            warn(text); throw new Error(text);
        }
    };
    function blockOffline(event) {
        if (navigator.onLine !== false) return false;
        event?.preventDefault(); event?.stopImmediatePropagation(); warn(offlineMessage); return true;
    }
    document.addEventListener('submit', event => {
        if (isInternal(event.target.action)) blockOffline(event);
    }, true);
    document.addEventListener('click', event => {
        const link = event.target.closest?.('a[href]');
        if (!link || !isInternal(link.href)) return;
        const url = new URL(link.href);
        if (url.pathname === location.pathname && url.search === location.search && url.hash) return;
        if (link.getAttribute('href').startsWith('#')) return;
        blockOffline(event);
    }, true);
    const nativeSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function () {
        if (isInternal(this.action) && blockOffline()) return;
        return nativeSubmit.call(this);
    };
    window.addEventListener('offline', () => warn(offlineMessage));
    window.addEventListener('online', checkConnection);
    document.addEventListener('DOMContentLoaded', render);
    if (navigator.onLine === false) warn(offlineMessage);

    // Network-only navigation fallback, including direct navigation while offline.
    // No quotations, account pages, uploads or credentials are cached.
    if ('serviceWorker' in navigator && window.isSecureContext) {
        navigator.serviceWorker.register(new URL('connection-worker.js', base), {scope:base.pathname, updateViaCache:'none'}).catch(() => {});
    }
})();
