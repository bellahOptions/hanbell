import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

/*
 * Dev-server host.
 *
 * The Laravel plugin writes the dev server's address into `public/hot`, and
 * Blade then emits asset URLs pointing at it. If that address is not reachable
 * from the browser, the page loads with NO CSS AT ALL — and because Vite serves
 * CSS as a JavaScript module in dev, the failure is silent: the <link> 404s and
 * the page simply renders unstyled.
 *
 * Node on Windows resolves `localhost` to IPv6 `::1` first, which is why the
 * default produced `http://[::1]:5173` and broke for a browser on 127.0.0.1.
 * Pinning both the bind address and the advertised origin to VITE_DEV_HOST
 * (127.0.0.1 by default) keeps the two in step.
 */
const devHost = process.env.VITE_DEV_HOST ?? '127.0.0.1';
const devPort = Number(process.env.VITE_DEV_PORT ?? 5173);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: devHost,
        port: devPort,
        strictPort: true,
        // What gets written into public/hot. Must be reachable by the browser.
        origin: `http://${devHost}:${devPort}`,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
