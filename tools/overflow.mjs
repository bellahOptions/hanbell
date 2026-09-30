/**
 * Finds which element is forcing the document wider than the viewport, and
 * reports its ancestor chain so the cause is unambiguous.
 *
 * Usage: node tools/overflow.mjs <url> [width] [height]
 */

import { spawn } from 'node:child_process';
import { mkdirSync, rmSync, existsSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const [, , url, widthArg, heightArg] = process.argv;

if (!url) {
    console.error('usage: node tools/overflow.mjs <url> [width] [height]');
    process.exit(1);
}

const width = Number(widthArg ?? 390);
const height = Number(heightArg ?? 900);

const chromePath = [
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
].find((p) => existsSync(p));

if (!chromePath) {
    console.error('No Chrome or Edge binary found.');
    process.exit(1);
}

const profile = join(tmpdir(), `hb-ov-${Date.now()}`);
mkdirSync(profile, { recursive: true });
const port = 9800 + Math.floor(Math.random() * 300);

const chrome = spawn(chromePath, [
    '--headless=new', '--disable-gpu', '--no-sandbox', '--no-first-run',
    '--disable-extensions', '--hide-scrollbars',
    `--window-size=${width},${height}`,
    `--remote-debugging-port=${port}`,
    `--user-data-dir=${profile}`,
    'about:blank',
], { stdio: 'ignore' });

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function endpoint() {
    for (let i = 0; i < 60; i++) {
        try {
            const r = await fetch(`http://127.0.0.1:${port}/json/version`);
            const j = await r.json();
            if (j.webSocketDebuggerUrl) return j.webSocketDebuggerUrl;
        } catch { /* not ready */ }
        await sleep(250);
    }
    throw new Error('devtools not available');
}

await endpoint();
const t = await (await fetch(`http://127.0.0.1:${port}/json/new?about:blank`, { method: 'PUT' })).json();
const ws = new WebSocket(t.webSocketDebuggerUrl);

let id = 1;
const pending = new Map();
ws.addEventListener('message', (e) => {
    const m = JSON.parse(e.data);
    if (m.id && pending.has(m.id)) {
        const { resolve, reject } = pending.get(m.id);
        pending.delete(m.id);
        m.error ? reject(new Error(JSON.stringify(m.error))) : resolve(m.result);
    }
});
const send = (method, params = {}) => new Promise((resolve, reject) => {
    const i = id++;
    pending.set(i, { resolve, reject });
    ws.send(JSON.stringify({ id: i, method, params }));
});

await new Promise((r) => ws.addEventListener('open', r, { once: true }));
await send('Page.enable');
await send('Runtime.enable');
await send('Page.navigate', { url });

await new Promise((resolve) => {
    const timer = setTimeout(resolve, 12000);
    ws.addEventListener('message', function h(e) {
        const m = JSON.parse(e.data);
        if (m.method === 'Page.loadEventFired') { clearTimeout(timer); ws.removeEventListener('message', h); resolve(); }
    });
});
await sleep(2000);

const expr = `(() => {
  const vw = document.documentElement.clientWidth;
  const rows = [];
  const all = document.querySelectorAll('*');
  let widest = null, widestRight = 0;
  for (const el of all) {
    const r = el.getBoundingClientRect();
    if (r.width === 0) continue;
    if (r.right > widestRight) { widestRight = r.right; widest = el; }
  }
  const chain = [];
  let node = widest;
  while (node && node !== document.documentElement) {
    const r = node.getBoundingClientRect();
    const cs = getComputedStyle(node);
    chain.push(
      node.tagName.toLowerCase()
      + (node.id ? '#' + node.id : '')
      + (typeof node.className === 'string' && node.className.trim()
          ? '.' + node.className.trim().split(/\\s+/).slice(0, 5).join('.') : '')
      + '  w=' + Math.round(r.width)
      + ' right=' + Math.round(r.right)
      + ' display=' + cs.display
      + ' width=' + cs.width
      + ' minWidth=' + cs.minWidth
      + ' overflowX=' + cs.overflowX
    );
    node = node.parentElement;
  }

  // The widest few elements, for a broader view.
  const sorted = Array.from(all)
    .map(el => ({ el, r: el.getBoundingClientRect() }))
    .filter(x => x.r.width > 0)
    .sort((a, b) => b.r.right - a.r.right)
    .slice(0, 8)
    .map(x => x.el.tagName.toLowerCase()
      + (typeof x.el.className === 'string' && x.el.className.trim()
          ? '.' + x.el.className.trim().split(/\\s+/).slice(0, 4).join('.') : '')
      + ' w=' + Math.round(x.r.width) + ' right=' + Math.round(x.r.right));

  return JSON.stringify({
    viewport: vw,
    docScrollWidth: document.documentElement.scrollWidth,
    bodyScrollWidth: document.body.scrollWidth,
    widestChain: chain,
    widestFew: sorted,
  }, null, 2);
})()`;

const res = await send('Runtime.evaluate', { expression: expr, returnByValue: true });
console.log(res.result.value);

ws.close();
chrome.kill();
try { rmSync(profile, { recursive: true, force: true }); } catch { /* disposable */ }
