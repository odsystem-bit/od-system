import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        vue(),
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
    publicDir: 'public',
    build: {
        watch: false,
        // Disable externalizing image imports so Vite can properly process static assets.
        // rollupOptions: {
        //     external: (id) => id.startsWith('/images/'),
        // },
    },
});
