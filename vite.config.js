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
    
    server: {
        host: '0.0.0.0',
        port: 5137,
        strictPort: true,
        cors: true,
        origin: 'http://localhost:5137',

        hmr: {
            host: 'localhost',
        },

        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
