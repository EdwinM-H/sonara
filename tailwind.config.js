import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                display: ['"Bricolage Grotesque"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Escala de marca (ancla en 600 = primary, 900 = primary-deep)
                purple: {
                    50: '#F4F0FF',
                    100: '#EAE1FD',
                    200: '#D3C2FB',
                    300: '#B79AF5',
                    400: '#9A72EB',
                    500: '#7C4BDD',
                    600: '#5B2BD9',
                    700: '#4A21B0',
                    800: '#391A86',
                    900: '#2E1065',
                    950: '#1D0A42',
                },
                // Neutro violeta (reemplaza la escala gris estándar en toda la app)
                gray: {
                    50: '#FAF9FC',
                    100: '#F4F1FA',
                    200: '#ECE8F6',
                    300: '#E5DFF5',
                    400: '#C7BEDD',
                    500: '#8A7FB0',
                    600: '#6F6390',
                    700: '#564A78',
                    800: '#3A3057',
                    900: '#14101F',
                    950: '#0B0813',
                },
                ink: '#14101F',
                accent: '#C9FF4B',
                cream: '#FAF6EF',
                lavender: '#F4F0FF',
                canvas: '#EDE9F5',
            },
            boxShadow: {
                lift: '0 30px 60px -30px rgba(30,10,80,0.5)',
            },
            borderRadius: {
                xl2: '1.25rem',
            },
            keyframes: {
                floatUp: {
                    '0%': { opacity: '0', transform: 'translateY(28px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                pulseRing: {
                    '0%': { transform: 'scale(0.85)', opacity: '0.55' },
                    '100%': { transform: 'scale(1.5)', opacity: '0' },
                },
                waveMove: {
                    '0%, 100%': { transform: 'scaleY(0.25)' },
                    '50%': { transform: 'scaleY(1)' },
                },
                marqueeMove: {
                    '0%': { transform: 'translateX(0)' },
                    '100%': { transform: 'translateX(-50%)' },
                },
                caretBlink: {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0' },
                },
                growBar: {
                    '0%': { transform: 'scaleY(0)' },
                    '100%': { transform: 'scaleY(1)' },
                },
            },
            animation: {
                floatUp: 'floatUp 0.7s ease both',
                pulseRing: 'pulseRing 2.6s infinite',
                wave: 'waveMove 1s ease-in-out infinite',
                marquee: 'marqueeMove 26s linear infinite',
                caret: 'caretBlink 1s steps(1) infinite',
                growBar: 'growBar 0.9s ease both',
            },
        },
    },

    plugins: [forms],
};
