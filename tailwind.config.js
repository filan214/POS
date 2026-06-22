import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Deep indigo chrome — nav, sidebar, primary text
                ink: {
                    DEFAULT: '#16223A',
                    900: '#0F1829',
                    800: '#16223A',
                    700: '#1F3052',
                    600: '#2C4373',
                    500: '#3D5A93',
                },
                // Warm workspace paper
                paper: {
                    DEFAULT: '#F5F4F0',
                    dark: '#ECEAE3',
                },
                // Jade — primary action / totals / "go"
                jade: {
                    DEFAULT: '#0E9F6E',
                    50: '#E7F6EF',
                    100: '#C5EBD9',
                    600: '#0E9F6E',
                    700: '#0B7D58',
                    800: '#085C41',
                },
                // Amber — cash, positive money highlights
                amber: {
                    DEFAULT: '#E0A100',
                    50: '#FCF5E0',
                    100: '#F8E7B5',
                    600: '#E0A100',
                    700: '#B27E00',
                },
                // Chili — alerts, void, low-stock, discrepancy
                chili: {
                    DEFAULT: '#D8453A',
                    50: '#FBE9E7',
                    100: '#F5C7C2',
                    600: '#D8453A',
                    700: '#AE332A',
                },
            },
            boxShadow: {
                card: '0 1px 2px 0 rgba(16, 24, 40, 0.04), 0 1px 3px 0 rgba(16, 24, 40, 0.06)',
                lift: '0 8px 24px -6px rgba(16, 24, 40, 0.12), 0 2px 6px -2px rgba(16, 24, 40, 0.08)',
                panel: '0 1px 0 0 rgba(16, 24, 40, 0.04)',
            },
            borderRadius: {
                xl2: '1.25rem',
            },
            keyframes: {
                'slide-up': {
                    '0%': { opacity: '0', transform: 'translateY(6px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'pulse-dot': {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.35' },
                },
            },
            animation: {
                'slide-up': 'slide-up 0.25s ease-out both',
                'pulse-dot': 'pulse-dot 1.8s ease-in-out infinite',
            },
        },
    },
    plugins: [],
};
