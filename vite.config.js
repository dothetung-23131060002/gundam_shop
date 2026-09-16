import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        allowedHosts: true,
        host: true,
        // Không hardcode hmr.host theo domain ngrok (đổi mỗi phiên sẽ gây asset
        // trỏ sai host). Demo qua ngrok luôn dùng bản build (npm run build),
        // không chạy dev server song song. Muốn HMR qua tunnel thì set tạm
        // hmr.host theo domain của phiên đó rồi revert.
    },
});