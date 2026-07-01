import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/report-chart.js', 'resources/js/admin-report-chart.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
