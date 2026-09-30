/**
 * Minimal CDP screenshot tool.
 *
 * Chrome's `--window-size` and `--screenshot` flags proved unreliable here: the
 * captured PNG width did not match the requested window size, so "clipped"
 * layouts in a screenshot could not be trusted. Driving Chrome over the
 * DevTools Protocol and setting the viewport explicitly with
 * `Emulation.setDeviceMetricsOverride` gives an exact, reproducible width.
 *
 * Usage:
 *   node tools/shoot.mjs <url> <out.png> [width] [height] [fullPage]
 */

import { spawn } from 'node:child_process';
import { mkdirSync, writeFileSync, rmSync, existsSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, dirname } from 'node:path';

const [, , url, out, widthArg, heightArg, fullArg] = process.argv;

if (!url || !out) {
    console.error('usage: node tools/shoot.mjs <url> <out.png> [width] [height] [fullPage]');
    process.exit(1);
}

const width = Number(widthArg ?? 1440);
const height = Number(heightArg ?? 900);
const fullPage = fullArg === 'true';

const CHROME_CANDIDATES = [
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
];

const chromePath = CHROME_CANDIDATES.find((p) => existsSync(p));

if (!chromePath) {
    console.error('No Chrome or Edge binary found.');
    process.exit(1);
}

const profile = join(tmpdir(), `hb-cdp-${Date.now()}`);
mkdirSync(profile, { recursive: true });
mkdirSync(dirname(out), { recursive: true });

const port = 9333 + Math.floor(Math.random() * 400);

const chrome = spawn(chromePath, [
    '--headless=new',
    '--disable-gpu',
    '--no-sandbox',
    '--no-first-run',
    '--disable-extensions',
    '--hide-scrollbars',
    // Size the window at launch; --window-size is honoured for the initial tab
    // in a way the metrics override was not.
    `--window-size=${width},${Math.min(height, 2000)}`,
    `--remote-debugging-port=${port}`,
    `--user-data-dir=${profile}`,
    'about:blank',
], { stdio: 'ignore' });

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

/** Poll the DevTools HTTP endpoint until it answers. */
async function devtoolsUrl() {
    for (let i = 0; i < 60; i++) {
        try {
            const res = await fetch(`http://127.0.0.1:${port}/json/version`);
            const json = await res.json();
            if (json.webSocketDebuggerUrl) return json.webSocketDebuggerUrl;
        } catch {
            /* not up yet */
        }
        await sleep(250);
    }
    throw new Error('Chrome DevTools endpoint did not become available.');
}

const wsUrl = await devtoolsUrl();

// Open a blank target first.
//
// Emulation must be applied to a page we already control: creating the target
// with the destination URL navigates immediately, and the override can land
// after that navigation, which is silently discarded. That is what made the
// reported innerWidth ignore the requested width.
const targetRes = await fetch(`http://127.0.0.1:${port}/json/new?about:blank`, {
    method: 'PUT',
});
const target = await targetRes.json();

const ws = new WebSocket(target.webSocketDebuggerUrl);

let nextId = 1;
const pending = new Map();

ws.addEventListener('message', (event) => {
    const msg = JSON.parse(event.data);
    if (msg.id && pending.has(msg.id)) {
        const { resolve, reject } = pending.get(msg.id);
        pending.delete(msg.id);
        msg.error ? reject(new Error(JSON.stringify(msg.error))) : resolve(msg.result);
    }
});

const send = (method, params = {}) =>
    new Promise((resolve, reject) => {
        const id = nextId++;
        pending.set(id, { resolve, reject });
        ws.send(JSON.stringify({ id, method, params }));
    });

await new Promise((resolve) => ws.addEventListener('open', resolve, { once: true }));

await send('Page.enable');
await send('Runtime.enable');

/**
 * Viewport sizing.
 *
 * `Emulation.setDeviceMetricsOverride` was tried first and abandoned: in this
 * Chrome build the override is accepted but has no effect on layout — the page
 * kept reporting innerWidth 948 while the screenshot came back 390px wide and
 * unstyled, which is exactly the kind of misleading evidence this tool exists
 * to avoid. Launching Chrome at the requested window size and letting the page
 * be a single tab with no chrome does respect the width.
 */
await send('Page.navigate', { url });

// Wait for the load event, then a little longer for fonts and Livewire.
await new Promise((resolve) => {
    const timer = setTimeout(resolve, 12000);
    ws.addEventListener('message', function onMsg(event) {
        const msg = JSON.parse(event.data);
        if (msg.method === 'Page.loadEventFired') {
            clearTimeout(timer);
            ws.removeEventListener('message', onMsg);
            resolve();
        }
    });
});

await sleep(1800);

// Report the geometry so the caller can verify the viewport really is what
// was asked for — the mistake that made the earlier screenshots misleading.
const metrics = await send('Runtime.evaluate', {
    expression: `JSON.stringify({
        innerWidth: window.innerWidth,
        innerHeight: window.innerHeight,
        dpr: window.devicePixelRatio,
        docScrollWidth: document.documentElement.scrollWidth,
        bodyScrollWidth: document.body.scrollWidth,
        overflow: Array.from(document.querySelectorAll('*'))
            .filter(el => {
                const r = el.getBoundingClientRect();
                if (r.width === 0) return false;
                const cs = getComputedStyle(el);
                // Ignore elements inside an intentionally scrollable container.
                let p = el.parentElement, inScroller = false;
                while (p) {
                    const pcs = getComputedStyle(p);
                    if (pcs.overflowX === 'auto' || pcs.overflowX === 'scroll') { inScroller = true; break; }
                    p = p.parentElement;
                }
                return !inScroller && (r.right > window.innerWidth + 1 || r.left < -1);
            })
            .slice(0, 12)
            .map(el => el.tagName.toLowerCase()
                + (el.className && typeof el.className === 'string'
                    ? '.' + el.className.trim().split(/\\s+/).slice(0,3).join('.')
                    : '')
                + ' right=' + Math.round(el.getBoundingClientRect().right))
    })`,
    returnByValue: true,
});

/**
 * Capture the viewport only.
 *
 * `captureBeyondViewport` was tried and abandoned: Chrome resizes the emulated
 * page to fit the requested clip, so the metrics override was silently undone
 * and the reported innerWidth stopped matching what was asked for. A plain
 * viewport capture honours the emulation exactly, and the geometry below is the
 * source of truth for whether anything overflows.
 */
const shot = await send('Page.captureScreenshot', { format: 'png' });

writeFileSync(out, Buffer.from(shot.data, 'base64'));

console.log(JSON.stringify({ out, requested: { width, height }, geometry: JSON.parse(metrics.result.value) }, null, 2));

ws.close();
chrome.kill();

try {
    rmSync(profile, { recursive: true, force: true });
} catch {
    /* the profile dir is disposable */
}
