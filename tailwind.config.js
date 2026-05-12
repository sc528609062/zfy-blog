import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import aspectRatio from '@tailwindcss/aspect-ratio';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{js,ts,jsx,tsx,vue}',
        './themes/**/views/**/*.blade.php',
        './app/Livewire/**/*.php',
        './app/View/Components/**/*.php',
        './vendor/livewire/livewire/src/**/*.php',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                // A 蓝白游戏资源社区风默认主题
                primary: {
                    50:  '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    300: '#93c5fd',
                    400: '#60a5fa',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    800: '#1e40af',
                    900: '#1e3a8a',
                    950: '#172554',
                },
                ink: {
                    50:  '#f8fafc',
                    100: '#f1f5f9',
                    200: '#e2e8f0',
                    300: '#cbd5e1',
                    400: '#94a3b8',
                    500: '#64748b',
                    600: '#475569',
                    700: '#334155',
                    800: '#1e293b',
                    900: '#0f172a',
                    950: '#020617',
                },
                accent: {
                    DEFAULT: '#22c55e',
                    50:  '#f0fdf4',
                    500: '#22c55e',
                    600: '#16a34a',
                },
                vip: {
                    DEFAULT: '#f59e0b',
                    50:  '#fffbeb',
                    400: '#fbbf24',
                    500: '#f59e0b',
                    600: '#d97706',
                },
            },
            fontFamily: {
                sans: ['"Inter"', '"PingFang SC"', '"Microsoft YaHei"', '"Segoe UI"', 'system-ui', 'sans-serif'],
                mono: ['"JetBrains Mono"', '"Fira Code"', 'ui-monospace', 'monospace'],
            },
            boxShadow: {
                card: '0 1px 3px rgba(15, 23, 42, 0.06), 0 1px 2px rgba(15, 23, 42, 0.04)',
                'card-hover': '0 6px 24px rgba(37, 99, 235, 0.12)',
            },
            borderRadius: {
                xl: '14px',
                '2xl': '18px',
            },
            backgroundImage: {
                'hero-grid': 'linear-gradient(180deg, rgba(37,99,235,0.0) 60%, rgba(15,23,42,0.55) 100%), radial-gradient(circle at 30% 30%, rgba(96,165,250,0.4), transparent 55%)',
            },
        },
    },
    plugins: [forms, typography, aspectRatio],
};

