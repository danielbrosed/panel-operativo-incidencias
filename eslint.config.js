import js from '@eslint/js';
import reactHooks from 'eslint-plugin-react-hooks';
import globals from 'globals';
import tseslint from 'typescript-eslint';

export default tseslint.config(
    { ignores: ['public/**', 'vendor/**', 'node_modules/**', 'bootstrap/ssr/**'] },
    js.configs.recommended,
    ...tseslint.configs.recommended,
    reactHooks.configs.flat.recommended,
    {
        files: ['resources/js/**/*.{ts,tsx}'],
        languageOptions: { globals: globals.browser },
        rules: {
            'no-console': ['error', { allow: ['warn', 'error'] }],
        },
    },
);
