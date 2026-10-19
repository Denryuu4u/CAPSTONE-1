/* Browser-independent connection checks; no business data or external services. */
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'assets/connection.js'), 'utf8');
class Element {
    constructor(tag) { this.tag = tag; this.children = []; this.listeners = {}; }
    append(...children) { this.children.push(...children); }
    setAttribute() {}
    addEventListener(name, callback) { this.listeners[name] = callback; }
    querySelector(tag) { return this.children.find(child => child.tag === tag); }
}
function browser(base, fetch) {
    const listeners = {}, events = {}, registrations = [];
    const location = new URL('admin/settings.php', base);
    const document = {
        currentScript: {src:new URL('assets/connection.js', base).href},
        head: new Element('head'), body: new Element('body'),
        createElement: tag => new Element(tag),
        addEventListener: (name, callback) => { events[name] = callback; }
    };
    class Form { constructor() { this.action = new URL('save.php', base).href; this.sent = 0; } submit() { this.sent++; } }
    const navigator = {onLine:true, serviceWorker:{register:async (url, options) => {registrations.push([url, options]);}}};
    const window = {fetch, isSecureContext:true, addEventListener:(name, callback) => {listeners[name] = callback;}};
    vm.runInNewContext(source, {window, document, navigator, location, Request, URL, Error, AbortController, HTMLFormElement:Form, setTimeout, clearTimeout});
    return {window, document, navigator, listeners, events, registrations, Form, banner:()=>document.body.children[0]};
}
(async () => {
    for (const base of ['http://localhost/CAPSTONE-1/', 'https://railway-example.test/']) {
        let calls = 0;
        const app = browser(base, async () => {calls++; return new Response(null, {status:204, headers:{'X-App-Connection':'ok'}});});
        assert.equal(app.registrations[0][0].href, new URL('connection-worker.js', base).href);
        assert.equal(app.registrations[0][1].scope, new URL(base).pathname);
        app.navigator.onLine = false; app.listeners.offline();
        await assert.rejects(app.window.fetch(new URL('save.php',base), {method:'POST'}), /offline/);
        assert.equal(calls, 0, 'Known offline writes never reach the network');
        const form = new app.Form(); form.submit(); assert.equal(form.sent, 0);
        let prevented = false, stopped = false;
        app.events.submit({target:form, preventDefault(){prevented=true;}, stopImmediatePropagation(){stopped=true;}});
        assert(prevented && stopped, 'Offline forms are stopped before page handlers clear input');
        app.events.click({target:{closest:()=>({href:new URL('admin/reports.php',base).href, getAttribute:()=> 'reports.php'})}, preventDefault(){prevented=true;}, stopImmediatePropagation(){}});
        assert.match(app.banner().querySelector('p').textContent, /has not been sent/);
        app.navigator.onLine = true; await app.listeners.online();
        assert.equal(app.banner().hidden, true);
        form.submit(); assert.equal(form.sent, 1, 'Online forms retain their normal behavior');
        console.log('PASS: Offline actions, reconnect and worker scope at ' + base);
    }
    let requests = 0, reconnected = false;
    const interrupted = browser('https://railway-example.test/', async () => { requests++; if(reconnected)return new Response(null,{status:204,headers:{'X-App-Connection':'ok'}}); throw new TypeError('Failed to fetch'); });
    await assert.rejects(interrupted.window.fetch('/save.php', {method:'POST'}), /could not confirm/);
    assert.equal(requests, 1, 'A failed write is never retried automatically');
    await assert.rejects(interrupted.window.fetch('/poll.php'), /cannot reach/);
    assert.match(interrupted.banner().querySelector('p').textContent, /could not confirm/, 'Background polling cannot hide an uncertain save');
    reconnected = true; await interrupted.listeners.online();
    assert.match(interrupted.banner().querySelector('p').textContent, /check the latest records/, 'Reconnecting must not imply an interrupted action succeeded');
    assert.equal(requests, 3, 'Reconnection only probes connectivity; it never repeats the save');
    const aborted = browser('https://railway-example.test/', async () => {const error=new Error('Cancelled');error.name='AbortError';throw error;});
    await assert.rejects(aborted.window.fetch('/read.php'), {name:'AbortError'});
    assert.equal(aborted.document.body.children.length, 0, 'Intentional cancellation is not reported as offline');
    const serverError = browser('https://railway-example.test/', async () => new Response('Server error',{status:500}));
    assert.equal((await serverError.window.fetch('/save.php')).status, 500, 'HTTP errors retain existing application handling');
    console.log('PASS: Interrupted saves, no replay, polling, cancellation and HTTP failures');

    const workerSource = fs.readFileSync(path.join(root,'connection-worker.js'),'utf8');
    for (const scope of ['http://localhost/CAPSTONE-1/', 'https://railway-example.test/']) {
        const handlers = {};
        let requests=0, fail=true;
        const worker={location:new URL(scope),registration:{scope},clients:{claim:async()=>{}},skipWaiting:async()=>{},addEventListener:(name,callback)=>{handlers[name]=callback;}};
        vm.runInNewContext(workerSource,{self:worker,URL,Response,fetch:async()=>{requests++;if(fail)throw new TypeError('Offline');return new Response('real response',{status:404});}});
        for(const method of ['GET','POST']) {
            let result;
            handlers.fetch({request:{mode:'navigate',url:new URL('admin/action.php',scope).href,method},respondWith:value=>{result=value;}});
            const response=await result;
            assert.equal(response.status,503);
            const html=await response.text();
            assert.match(html,/Connection problem/);
            assert(html.includes(new URL('index.php',scope).pathname));
            if(method==='POST')assert.match(html,/could not confirm/);
        }
        fail=false;let result;
        handlers.fetch({request:{mode:'navigate',url:scope,method:'GET'},respondWith:value=>{result=value;}});
        assert.equal((await result).status,404,'Real HTTP error pages are preserved');
        handlers.fetch({request:{mode:'cors',url:scope,method:'POST'},respondWith:()=>assert.fail('Worker must not intercept API calls')});
        assert.equal(requests,3);
    }
    console.log('PASS: Network-only offline navigation at local and Railway paths; no cached account data');
})().catch(error => {console.error(error);process.exitCode=1;});
