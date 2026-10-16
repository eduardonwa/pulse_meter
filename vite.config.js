import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/styles/main.scss',
                'resources/js/app.js',
                'resources/js/filament/admin.js',
                'resources/css/filament/admin/theme.css'
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],

    // Mobile testing (WSL2):
    // 1. Find Windows LAN IP: ipconfig
    // 2. Forward ports 8000 (Laravel) and 5137 (Vite) to WSL2 using netsh portproxy.
    // 3. Allow both ports through Windows Firewall.
    // 4. Update origin and hmr.host if the Windows LAN IP changes.
    // 5. Open http://WINDOWS_IP:8000 on your phone (same network).
    server: {
        host: '0.0.0.0',
        port: 5137,
        strictPort: true,
        cors: true,
        origin: 'http://192.168.1.87:5137',

        hmr: {
            host: '192.168.1.87',
        },

        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
