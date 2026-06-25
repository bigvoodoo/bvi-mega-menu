import { defineConfig, globalIgnores } from 'eslint/config';
import wordpress from '@wordpress/eslint-plugin';

export default defineConfig([
  globalIgnores(['assets/dist/**/*', 'assets/src/vendors/**/*', '**/gulpfile.mjs', '**/*.scss']),
  ...wordpress.configs.recommended,
  {
    files: ['assets/src/**/*.{js,jsx}'],
    languageOptions: {
      globals: {
        jQuery: 'readonly',
      },
    },
    rules: {
      'prettier/prettier': 'off',
      'import/no-unresolved': ['error', { ignore: ['^@wordpress/', '^react$', '^react-dom$'] }],
      'import/no-extraneous-dependencies': 'off',
      'react-hooks/exhaustive-deps': ['error', { additionalHooks: 'useSelect' }],
      'import/order': [
        'error',
        {
          groups: ['builtin', ['external', 'unknown'], 'internal', 'parent', 'sibling', 'index'],
        },
      ],
      '@wordpress/dependency-group': 'error',
      '@wordpress/react-no-unsafe-timeout': 'error',
      'jsdoc/check-indentation': 'error',
      'wrap-iife': ['error', 'inside'],
    },
  },
]);
