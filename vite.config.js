import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        vue({
            template: {
                transformAssetUrls: {
                    // Absolute URLs (e.g. src="/images/logo.png") are served from public/
                    // and must not be turned into module imports by the SFC compiler.
                    includeAbsolute: false,
                },
            },
        }),
        laravel({
            input: [
                'resources/js/app.js',
                'resources/js/admin.js',
                'resources/js/vendor.js',
                'resources/js/influencer.js',
                'resources/css/app.css',
                'resources/css/admin.css',
                'resources/css/vendor.css',
                'resources/css/influencer.css',
            ],
            refresh: true,
        }),
    ],
    server: {
        hmr: false,
    },
    build: {
        watch: false,
    },
});
