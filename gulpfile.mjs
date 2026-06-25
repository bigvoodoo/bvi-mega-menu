// source of all your asset files to be processed (should have an /assets folder relative to this path)
const templatePath = '.';

// should match the namespace/prefix you are utilizing through your project
const projectNamespace = 'bvi-mega-menu';

// list of vendor script dependencies from NPM you want to include in your projects runtime
const vendorScripts = [];
/**
 * ////////////////////////////////////////////////////////////////////////////
 * //////////////////// DO NOT EDIT PAST THIS POINT ///////////////////////////
 * ////////////////////////////////////////////////////////////////////////////
 */

/**
 * External dependencies
 */
import { src, dest, watch, series, parallel } from 'gulp';
import { exec } from 'child_process';
import fs from 'fs';
import path from 'path';
import crypto from 'crypto';
import { deleteAsync } from 'del';
import gulpSass from 'gulp-sass';
import * as dartSass from 'sass';
import rename from 'gulp-rename';
import terser from 'gulp-terser';
import cleanCss from 'gulp-clean-css';
import postcss from 'gulp-postcss';
import autoprefixer from 'autoprefixer';
import sharp from 'sharp';

// esmodule building
import gulpEsbuild from 'gulp-esbuild';
import esbuild from 'esbuild';

/**
 * Internal dependencies
 */
const sass = gulpSass(dartSass);

// global holder for current watchers for start tasks
let activeWatchers = [];

// leave these alone
const distPath = templatePath + '/assets/dist';
const srcPath = templatePath + '/assets/src';
const sassSrcPath = srcPath + '/sass';
const sassDistPath = distPath + '/css';
const scriptSrcPath = srcPath + '/scripts';
const scriptDistPath = distPath + '/scripts';
const imageSrcPath = srcPath + '/images';
const imageDistPath = distPath + '/images';
const blocksSrcPath = srcPath + '/blocks';
const blocksDistPath = distPath + '/blocks';
const handlebarsTemplatePath = templatePath + '/assets/templates';

/**
 * Check if directory exists, create if not
 *
 * @param {string} filePath path to check
 * @return {boolean} true if exists or created
 */
export function checkDirectoryExists(filePath) {
  const dirname = path.dirname(filePath);
  if (fs.existsSync(dirname)) {
    return true;
  }
  fs.mkdirSync(dirname, { recursive: true });
  console.log(`Created directory: ${dirname}`);
  return true;
}

/**
 * Generates the styles for the project.
 *
 * @return {Object} gulp stream
 */
export async function styles() {
  const sassSrc = sassSrcPath;
  const cssDist = sassDistPath;

  if (!fs.existsSync(sassSrc)) {
    console.warn('No styles found to process.');
    return Promise.resolve();
  }

  if (!fs.existsSync(cssDist)) {
    fs.mkdirSync(cssDist, { recursive: true });
    console.log(`Created directory: ${cssDist}`);
  }

  // process already minified files (including subfolders)
  const minified = src(`${sassSrc}/**/*.css`)
    .pipe(dest(cssDist))
    .on('data', (file) => {
      console.log('Processed minified file:', file.path);
    });

  // process files that need minification (including subfolders)
  const toMinify = src([`${templatePath}/assets/src/sass/*.scss`])
    .pipe(sass().on('error', sass.logError))
    .pipe(postcss([autoprefixer()]))
    .pipe(
      cleanCss({
        compatibility: '*',
        level: {
          1: {
            specialComments: 0,
          },
        },
      })
    )
    .pipe(dest(cssDist));

  return Promise.all(
    [minified, toMinify].map(
      (stream) =>
        new Promise((resolve, reject) => {
          stream.on('end', resolve).on('error', reject);
        })
    )
  );
}

/**
 * Compiles block SCSS files into their dist directories.
 *
 * Scans assets/src/blocks/<slug>/style.scss and outputs each to
 * assets/dist/blocks/<slug>/style-index.css so block.json
 * can reference it via "style": "file:./style-index.css".
 *
 * @return {Promise} promise that resolves when all block styles are compiled
 */
export function blockStyles() {
  const blockStyleGlob = blocksSrcPath + '/*/style.scss';

  if (!fs.existsSync(blocksSrcPath)) {
    console.warn('No blocks directory found. Skipping block styles.');
    return Promise.resolve();
  }

  return src(blockStyleGlob, { allowEmpty: true })
    .pipe(sass().on('error', sass.logError))
    .pipe(postcss([autoprefixer()]))
    .pipe(
      cleanCss({
        compatibility: '*',
        level: {
          1: {
            specialComments: 0,
          },
        },
      })
    )
    .pipe(rename('style-index.css'))
    .pipe(
      dest(function (file) {
        const blockSlug = path.basename(path.dirname(file.history[0]));
        return path.join(blocksDistPath, blockSlug);
      })
    )
    .on('data', (file) => {
      console.log('Compiled block style:', file.path);
    });
}

/**
 * Esbuild plugin that maps @wordpress/* and react imports to browser globals.
 *
 * @param {Set} deps Set to collect WordPress script handles into.
 * @return {Object} esbuild plugin
 */
function createWpExternalsPlugin(deps) {
  return {
    name: 'wordpress-externals',
    setup(build) {
      build.onResolve({ filter: /^@wordpress\/|^react(-dom)?$|^react\/jsx-runtime$/ }, (args) => {
        // @wordpress/icons is not a registered WP script handle — bundle it locally.
        if (args.path === '@wordpress/icons') {
          return;
        }
        return { path: args.path, namespace: 'wp-globals' };
      });

      build.onLoad({ namespace: 'wp-globals', filter: /.*/ }, (args) => {
        let global;
        let handle;

        if (args.path === 'react') {
          global = 'React';
          handle = 'react';
        } else if (args.path === 'react-dom') {
          global = 'ReactDOM';
          handle = 'react-dom';
        } else if (args.path === 'react/jsx-runtime') {
          global = 'ReactJSXRuntime';
          handle = 'react-jsx-runtime';
        } else {
          const slug = args.path.replace('@wordpress/', '');
          const camelCase = slug.replace(/-([a-z])/g, (_, c) => c.toUpperCase());
          global = `wp.${camelCase}`;
          handle = `wp-${slug}`;
        }

        deps.add(handle);

        return {
          contents: `module.exports = window.${global};`,
          loader: 'js',
        };
      });
    },
  };
}

/**
 * Generates a WordPress .asset.php file content string.
 *
 * @param {Set}    deps        Set of WP script handles.
 * @param {string} contentHash Hash of the built file for cache busting.
 * @return {string} PHP file contents
 */
function generateAssetPhp(deps, contentHash) {
  const sorted = [...deps]
    .sort()
    .map((d) => `'${d}'`)
    .join(', ');
  return `<?php return array('dependencies' => array(${sorted}), 'version' => '${contentHash}');\n`;
}

/**
 * Builds block editor and view scripts with esbuild.
 *
 * For each block in assets/src/blocks/<slug>/:
 *   - Bundles index.jsx (editor) and view.jsx (frontend) with esbuild
 *   - Maps @wordpress/* imports to browser globals (not bundled)
 *   - Generates .asset.php with WP script handle dependencies
 *   - Copies block.json to the output directory
 *
 * @return {Promise} promise that resolves when all block scripts are built
 */
export async function blockScripts() {
  if (!fs.existsSync(blocksSrcPath)) {
    console.warn('No blocks directory found. Skipping block scripts.');
    return;
  }

  const blockDirs = fs
    .readdirSync(blocksSrcPath, { withFileTypes: true })
    .filter((d) => d.isDirectory() && fs.existsSync(path.join(blocksSrcPath, d.name, 'block.json')));

  for (const dir of blockDirs) {
    const blockDir = path.join(blocksSrcPath, dir.name);
    const outDir = path.join(blocksDistPath, dir.name);

    fs.mkdirSync(outDir, { recursive: true });

    // Build each JS entry point (editor + view).
    for (const entry of ['index.js', 'view.js']) {
      const baseName = entry.replace('.js', '');
      const jsPath = path.join(blockDir, `${baseName}.js`);
      const jsxPath = path.join(blockDir, `${baseName}.jsx`);
      const entryPath = fs.existsSync(jsxPath) ? jsxPath : jsPath;

      if (!fs.existsSync(entryPath)) {
        continue;
      }

      const deps = new Set();

      await esbuild.build({
        entryPoints: [entryPath],
        bundle: true,
        minify: true,
        sourcemap: true,
        target: 'es2020',
        format: 'iife',
        jsx: 'transform',
        banner: { js: 'var React = window.React;' },
        outfile: path.join(outDir, entry),
        plugins: [createWpExternalsPlugin(deps)],
      });

      // Generate .asset.php alongside the built JS.
      const built = fs.readFileSync(path.join(outDir, entry));
      const hash = crypto.createHash('md5').update(built).digest('hex').slice(0, 16);
      const assetPath = path.join(outDir, entry.replace('.js', '.asset.php'));
      fs.writeFileSync(assetPath, generateAssetPhp(deps, hash));

      console.log(`Built block script: ${dir.name}/${entry} (deps: ${[...deps].join(', ') || 'none'})`);
    }

    // Copy block.json to output.
    const srcJson = path.join(blockDir, 'block.json');
    const destJson = path.join(outDir, 'block.json');
    if (fs.existsSync(srcJson)) {
      fs.copyFileSync(srcJson, destJson);
      console.log(`Copied block.json: ${dir.name}`);
    }
  }
}

/**
 * Generates a list of image files and performs various optimization operations on them.
 *
 * @param {Function} callback gulp callback
 * @return {Object} gulp stream
 */
export async function images(callback) {
  const imageSrc = imageSrcPath;
  const imageDist = imageDistPath;
  const imageFiles = `${imageSrc}/**/*.{jpg,jpeg,png,webp}`;

  return src(imageFiles)
    .on('data', (file) => {
      const extension = path.extname(file.path).toLowerCase();
      let pipeline = sharp(file.path);

      switch (extension) {
        case '.jpg':
        case '.jpeg':
          pipeline = pipeline.jpeg({
            mozjpeg: true,
            quality: 40,
          });
          break;

        case '.png':
          pipeline = pipeline.png({
            compressionLevel: 9,
            adaptiveFiltering: true,
            quality: 60,
            palette: true,
          });
          break;

        case '.webp':
          pipeline = pipeline.webp({
            quality: 40,
          });
          break;

        default:
          console.warn(`Unsupported file type: ${file.path}`);
          return;
      }

      const relativePath = path.relative(file.base, file.path);
      const outputFilePath = path.join(imageDist, relativePath);

      checkDirectoryExists(outputFilePath);

      pipeline
        .toFile(outputFilePath)
        .then(() => {
          console.log('Processed image:', file.path);
        })
        .catch((err) => {
          console.error('Error during image processing:', err.message);
        });
    })
    .on('error', (err) => {
      console.error('Error during image processing:', err.message);
      callback(err);
    })
    .on('end', () => {
      console.log('Images processed successfully.');
      callback();
    });
}

/**
 * Concatenates and minifies JavaScript files.
 * Processes files in subfolders while maintaining directory structure.
 *
 * @return {Promise} promise that resolves when all scripts are processed
 */
export function scripts() {
  if (!fs.existsSync(scriptSrcPath)) {
    console.warn('no scripts found to process.');
    return Promise.resolve();
  }

  if (!fs.existsSync(scriptDistPath)) {
    fs.mkdirSync(scriptDistPath, { recursive: true });
    console.log(`Created directory: ${scriptDistPath}`);
  }

  const entryPoint = `${scriptSrcPath}`;

  return src([`${scriptSrcPath}/**/*.js`, `!${scriptSrcPath}/**/*.min.js`])
    .on('data', (file) => {
      console.log('Processing:', file.path);
    })
    .pipe(
      gulpEsbuild({
        bundle: true,
        minify: true,
        sourcemap: true,
        target: 'es2020',
      })
    )
    .on('error', (err) => {
      console.error('esbuild error:', err.message);
    })
    .pipe(
      rename(function (path) {
        path.extname = '.min.js';
        path.basename = path.basename.replace('.js', '');
      })
    )
    .pipe(dest(scriptDistPath))
    .on('data', (file) => {
      console.log('Output:', file.path);
    });
}

/**
 * Runs Composer install/update in project directory.
 *
 * @param {Function} done gulp callback
 * @return {void}
 */
export function runComposer(done) {
  if (
    !fs.existsSync(templatePath + '/vendor') ||
    fs.statSync(templatePath + '/composer.lock').mtime > fs.statSync(templatePath + '/vendor').mtime
  ) {
    exec('composer install', (err, stdout, stderr) => {
      console.log(stdout);
      console.error(stderr);
      done(err);
    });
  } else {
    console.log('Vendor is up to date.');
    done();
  }
}

/**
 * Copies the specified files and directories to the destination directory.
 *
 * @return {Promise} promise that resolves when all copying is completed
 */
export function copy() {
  const distFiles = src(
    [
      srcPath + '/**/*',
      '!' + imageSrcPath + '/**/*.{jpg,jpeg,png,webp}',
      '!' + scriptSrcPath + '{,/**}',
      '!' + sassSrcPath + '{,/**}',
      '!' + blocksSrcPath + '{,/**}',
    ],
    { base: srcPath }
  )
    .pipe(dest(distPath))
    .on('data', (file) => {
      console.log('Copied file:', file.path);
    });

  const vendorFiles =
    Array.isArray(vendorScripts) && vendorScripts.length
      ? src(vendorScripts)
          .pipe(dest(scriptDistPath + '/vendor'))
          .on('data', (file) => {
            console.log('Copied vendor file:', file.path, ' to ', scriptDistPath + '/vendor');
          })
      : Promise.resolve();

  return Promise.all(
    [distFiles, vendorFiles].map((streamOrPromise) =>
      streamOrPromise instanceof Promise
        ? streamOrPromise
        : new Promise((resolve, reject) => {
            streamOrPromise.on('end', resolve).on('error', reject);
          })
    )
  );
}

/**
 * Removes all existing files in the dist folder.
 *
 * @return {Promise} promise that resolves when files are deleted
 */
export async function clean() {
  return await deleteAsync([distPath]);
}

/**
 * Creates the dist directory structure
 *
 * @return {Promise} promise that resolves when directories are created
 */
export async function createDistDirs() {
  const dirs = [distPath, sassDistPath, scriptDistPath, imageDistPath];

  try {
    for (const dir of dirs) {
      if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
        console.log(`Created directory: ${dir}`);
      }
    }
  } catch (error) {
    console.error(`Error creating directories: ${error.message}`);
    throw error;
  }
}

/**
 * Build tasks dynamically based on what directories exist
 *
 * @return {Array} array of gulp tasks
 */
export function getBuildTasks() {
  const tasks = [clean, createDistDirs, runComposer];
  const parallelTasks = [];

  // only add tasks for directories that exist
  if (fs.existsSync(sassSrcPath)) {
    parallelTasks.push(styles);
  } else {
    console.warn('No sass directory found. Skipping styles task.');
  }

  if (fs.existsSync(imageSrcPath)) {
    parallelTasks.push(images);
  } else {
    console.warn('No images directory found. Skipping images task.');
  }

  if (fs.existsSync(scriptSrcPath)) {
    parallelTasks.push(scripts);
  } else {
    console.warn('No scripts directory found. Skipping scripts task.');
  }

  if (fs.existsSync(blocksSrcPath)) {
    parallelTasks.push(blockStyles);
    parallelTasks.push(blockScripts);
  } else {
    console.warn('No blocks directory found. Skipping block tasks.');
  }

  if (fs.existsSync(handlebarsTemplatePath)) {
    parallelTasks.push(generateTemplates);
  } else {
    console.warn('No handlebar templates directory found. Skipping generateTemplates task.');
  }

  if (fs.existsSync(srcPath)) {
    parallelTasks.push(copy);
  } else {
    console.warn('No src directory found. Skipping copy task.');
  }

  // add parallel tasks if any exist
  if (parallelTasks.length > 0) {
    tasks.push(parallel(...parallelTasks));
  }

  return tasks;
}

export function startWatching() {
  const sassPath = sassSrcPath + '/**/*.scss';
  const scriptsPath = scriptSrcPath + '/**/*.js';
  const imagesPath = imageSrcPath + '/**/*.{jpg,jpeg,png,svg,gif}';

  // only watch directories that exist
  if (fs.existsSync(sassSrcPath)) {
    activeWatchers.push(watch([sassPath], styles));
    console.log('Watching SASS files...');
  }

  if (fs.existsSync(scriptSrcPath)) {
    activeWatchers.push(watch([scriptsPath], scripts));
    console.log('Watching script files...');
  }

  if (fs.existsSync(imageSrcPath)) {
    activeWatchers.push(watch([imagesPath], images));
    console.log('Watching image files...');
  }

  if (fs.existsSync(blocksSrcPath)) {
    const blockStylesPath = blocksSrcPath + '/**/*.scss';
    const blockScriptsPath = blocksSrcPath + '/**/*.{js,jsx}';
    activeWatchers.push(watch([blockStylesPath], blockStyles));
    activeWatchers.push(watch([blockScriptsPath], blockScripts));
    console.log('Watching block files...');
  }

  if (fs.existsSync(handlebarsTemplatePath)) {
    activeWatchers.push(watch([handlebarsTemplatePath], generateTemplates));
    console.log('Watching handlebar template files...');
  }

  const cleanup = () => {
    stop();
    process.exit(0);
  };

  process.on('SIGINT', cleanup);
  process.on('SIGTERM', cleanup);
  process.on('SIGHUP', cleanup);
}

export function stop(done) {
  if (activeWatchers.length > 0) {
    console.log('Closing existing watchers...');
    activeWatchers.forEach((watcher) => watcher.close());
    activeWatchers = [];
  }

  return Promise.resolve();
}

// runs the build task of the project.
export const build = series(...getBuildTasks());

// runs the dev task of the project, which does the build task and watches for changes.
export const dev = series(stop, build, startWatching);

// default run is the dev task if nothing is specified.
export default dev;
