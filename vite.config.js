import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    build: {
        // Không transpile xuống ES5 — tránh “legacy” polyfill trong bundle first-party (Vite/esbuild).
        target: "es2022",
        cssTarget: "chrome107",
    },
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
    ],
    server: {
        host: true,
        allowedHosts: [".ngrok-free.app"], // 👈 Thêm dòng này
    },
});
