import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./app/Filament/**/*.php",
        "./resources/js/**/*.js",
        "./resources/js/**/*.jsx",
        "./resources/js/**/*.ts",
        "./resources/js/**/*.tsx",
    ],

    // Safelist các class dùng cho layout sticky sidebar (đã có sẵn, giữ nguyên)
    safelist: [
        'flex-1',
        'min-h-0',
        'overflow-y-auto',
        'shrink-0',
        'h-full',
        'lg:items-stretch',
        'lg:sticky',
        'lg:top-4',
        'lg:top-10',
        'lg:self-start',
        // Nếu bạn dùng thêm class nào khác cho sticky, có thể bổ sung ở đây
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
                display: ["Plus Jakarta Sans", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: "#f0427c",
                "background-light": "#f8f6f6",
                "background-dark": "#221016",
            },
            borderRadius: {
                DEFAULT: "0.25rem",
                lg: "0.5rem",
                xl: "0.75rem",
                full: "9999px",
            },
        },
    },

    plugins: [forms],
};