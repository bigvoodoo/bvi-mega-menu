import { createRequire } from 'module';
const require = createRequire(import.meta.url);
const wordPressConfig = require('@wordpress/prettier-config');

export default {
  ...wordPressConfig,
  useTabs: false,
  tabWidth: 4,
  singleQuote: true,
  printWidth: 120,
  singleAttributePerLine: false,
  bracketSameLine: false,
  braceStyle: 'per-cs',
  embeddedLanguageFormatting: 'auto',
  htmlWhitespaceSensitivity: 'css',
  plugins: [...(wordPressConfig.plugins || []), '@prettier/plugin-php'],
  phpVersion: '8.3',
  overrides: [
    ...(wordPressConfig.overrides || []),
    {
      files: ['*.html', '*.htm'],
      options: { parser: 'html' },
    },
    {
      files: ['*.scss', '*.sass'],
      options: {
        parser: 'scss',
        printWidth: 9999,
        proseWrap: 'preserve',
      },
    },
    {
      files: ['*.php'],
      options: { braceStyle: 'per-cs' },
    },
    {
      files: ['*.yml', '*.yaml', '*.json', '*.js', '*.jsx', '*.mjs', '*.cjs', '*.ts', '*.mts', '*.cts'],
      options: { tabWidth: 2 },
    },
  ],
};
