import js from '@eslint/js';
import prettier from 'eslint-config-prettier';
import prettierPlugin from 'eslint-plugin-prettier';
import htmlParser from '@html-eslint/parser';
import htmlPlugin from '@html-eslint/eslint-plugin';

export default [
    // JavaScript/TypeScript用の設定
    {
        files: ['**/*.js', '**/*.ts', '**/*.jsx', '**/*.tsx'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: {
                console: 'readonly',
                process: 'readonly',
                Buffer: 'readonly',
                __dirname: 'readonly',
                __filename: 'readonly',
                global: 'readonly',
                window: 'readonly',
                document: 'readonly',
                navigator: 'readonly',
                localStorage: 'readonly',
                sessionStorage: 'readonly',
                fetch: 'readonly',
                alert: 'readonly',
                confirm: 'readonly',
                prompt: 'readonly',
            },
        },
        plugins: {
            prettier: prettierPlugin,
        },
        rules: {
            ...js.configs.recommended.rules,
            ...prettier.rules,
            'prettier/prettier': 'error',
            'no-unused-vars': 'warn',
            'no-console': 'warn',
            'prefer-const': 'error',
            'no-var': 'error',
            eqeqeq: 'error',
            curly: 'error',
        },
    },
    // HTML用の設定
    {
        files: ['**/*.html', '**/*.blade.php'],
        languageOptions: {
            parser: htmlParser,
        },
        plugins: {
            '@html-eslint': htmlPlugin,
        },
        rules: {
            ...htmlPlugin.configs.recommended.rules,
            '@html-eslint/require-img-alt': 'error',
            '@html-eslint/require-button-type': 'error',
            '@html-eslint/no-target-blank': 'error',
            '@html-eslint/no-duplicate-attrs': 'error',
            '@html-eslint/no-extra-spacing-attrs': 'off',
            '@html-eslint/no-multiple-h1': 'warn',
            '@html-eslint/require-closing-tags': 'off',
        },
    },
    // 除外ファイル
    {
        ignores: [
            'node_modules/**',
            'vendor/**',
            'storage/**',
            'bootstrap/cache/**',
            'public/build/**',
        ],
    },
];
