/* Network-only worker: provides an offline navigation message, never cached data. */
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', event => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', event => {
    if (event.request.mode !== 'navigate' || new URL(event.request.url).origin !== self.location.origin) return;
    event.respondWith(fetch(event.request).catch(() => {
        const writing = event.request.method !== 'GET';
        const message = writing
            ? 'The connection was interrupted. We could not confirm whether your action completed. Reconnect and check the latest records before trying again.'
            : 'We cannot reach the system. Please check your internet connection and try again when you are online.';
        const home = new URL('./index.php', self.registration.scope).pathname;
        return new Response('<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Connection problem – Vast Solutions</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f4f7f6;font:16px/1.6 Arial,sans-serif;color:#1f2937}main{max-width:460px;margin:24px;padding:32px;background:white;border:1px solid #dce5e1;border-radius:16px}h1{font-size:25px}a{display:inline-block;background:#0d9676;color:white;padding:10px 18px;border-radius:7px;text-decoration:none}</style></head><body><main role="alert"><h1>Connection problem</h1><p>' + message + '</p><p>The system will not automatically resend your action.</p><a href="' + home + '">Return to the system</a></main></body></html>', {status:503, headers:{'Content-Type':'text/html; charset=utf-8','Cache-Control':'no-store'}});
    }));
});
