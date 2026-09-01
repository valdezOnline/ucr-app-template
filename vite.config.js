import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                input: [
                    'resources/sass/app.scss',
                    'resources/css/app.css',
                    'resources/css/custom.css',
                    'resources/js/app.js',
                ],
                refresh: true,
            }),
        ],
        server: {
            host: '0.0.0.0',
            port: 6173,
            strictPort: true,
            hmr: {
                host: env.VITE_HMR_HOST || 'localhost',
                clientPort: 16173,
            },
        },
        css: {
            preprocessorOptions: {
                scss: {
                    sassOptions: {
                        quietDeps: true,
                    },
                    silenceDeprecations: [
                        'import',
                        'global-builtin',
                        'color-functions',
                    ],
                },
            },
        },
    };
});
