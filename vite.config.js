import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
    ],
    build: {
        manifest: 'manifest.json',
        outDir: 'public/build',
        publicDir: false,
        rollupOptions: {
            input: [
                'resources/css/app.css',
            ],
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
