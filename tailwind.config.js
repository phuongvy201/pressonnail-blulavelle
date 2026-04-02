import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./app/Filament/**/*.php",
        // Page content seeding (HTML strings contain Tailwind classes)
        "./database/seeders/**/*.php",
        "./resources/js/**/*.js",
        "./resources/js/**/*.jsx",
        "./resources/js/**/*.ts",
        "./resources/js/**/*.tsx",
    ],

    // Safelist các class dùng cho layout sticky sidebar + class trong nội dung page (DB)
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
        // Brand primary (#0195FE) + helpers often used in seeded HTML
        'bg-[#0195FE]', 'text-[#0195FE]', 'border-[#0195FE]',
        'from-[#0195FE]', 'via-[#0195FE]', 'to-[#0195FE]',
        'hover:bg-[#0195FE]', 'hover:text-[#0195FE]', 'hover:underline',
        'text-primary-fg', 'border-primary-fg/40',
        'bg-gradient-to-r', 'bg-gradient-to-br',
        'shadow-md', 'shadow-lg', 'shadow-xl', 'shadow-2xl', 'transition', 'duration-200',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
                display: ["Plus Jakarta Sans", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: "#0195FE",
                // Chữ/link thay text-primary trên nền sáng / pastel (WCAG AA)
                "primary-fg": "#052f4a",
                "background-light": "#f8f6f6",
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