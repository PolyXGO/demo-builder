/**
 * Demo Builder - Webpack Build Configuration
 * ============================================
 * 
 * @license MIT
 * @author PolyXGO
 * @version 1.0.0
 *
 * INSTALLATION:
 * -------------
 * 1. Ensure Node.js (v16+) and npm (v8+) are installed on your system
 * 2. Navigate to plugin directory:
 *    cd wp-content/plugins/demo-builder
 * 3. Install dependencies:
 *    npm install
 *
 * BUILD COMMANDS:
 * ---------------
 * - npm run build      : Build minified production assets
 * - npm run build:dev  : Build non-minified development assets  
 * - npm run watch      : Watch for changes and rebuild automatically
 * - npm run clean      : Remove dist/ directory
 *
 * CONFIGURATION SWITCHES:
 * -----------------------
 * - is_minified_js     : true/false - Toggle JS minification
 * - is_minified_css    : true/false - Toggle CSS minification
 * - is_console_remove  : true/false - Remove console.* statements in production
 *
 * OUTPUT STRUCTURE:
 * -----------------
 * - Source files: assets/js/, assets/css/
 * - Built files:  dist/assets/js/, dist/assets/css/
 * - Libraries:    assets/lib/ → dist/assets/lib/ (copied, NOT rebuilt)
 *
 * NOTES:
 * ------
 * - Library files in assets/lib/ (SweetAlert2, Vue) are copied directly
 * - Use dist/ files in production for better performance
 * - Set is_minified_* = false for debugging during development
 * - Toggle is_console_remove = false to keep console.log statements
 */

// === BUILD CONFIGURATION SWITCHES ===
const is_minified_js = true;     // Toggle JS minification
const is_minified_css = true;    // Toggle CSS minification
const is_console_remove = true;  // Remove console.* in production

// === DEPENDENCIES ===
const path = require("path");
const glob = require("glob");
const fs = require("fs-extra");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");
const TerserPlugin = require("terser-webpack-plugin");
const CopyWebpackPlugin = require("copy-webpack-plugin");

/**
 * Plugin: Clean dist folder before rebuild
 */
class CleanDistFolderPlugin {
    apply(compiler) {
        compiler.hooks.beforeRun.tapAsync(
            "CleanDistFolderPlugin",
            (compilation, callback) => {
                const distPath = path.resolve(__dirname, "dist");
                if (fs.existsSync(distPath)) {
                    fs.removeSync(distPath);
                }
                callback();
            }
        );
    }
}

/**
 * Plugin: Remove empty JS files generated for CSS-only entries
 */
class RemoveJsForCssPlugin {
    apply(compiler) {
        compiler.hooks.emit.tapAsync(
            "RemoveJsForCssPlugin",
            (compilation, callback) => {
                const cssFiles = Object.keys(compilation.assets).filter((filename) =>
                    filename.endsWith(".css")
                );

                cssFiles.forEach((cssFile) => {
                    const jsFile = cssFile.replace(/\.min\.css$/, ".js");
                    if (compilation.assets[jsFile]) {
                        delete compilation.assets[jsFile];
                    }
                });

                callback();
            }
        );
    }
}

// === AUTO-DISCOVER JS FILES ===
// Get all JS files from assets/js, excluding lib directory
const allJsFiles = glob.sync("./assets/js/**/*.js", {
    ignore: [
        "./assets/js/libs/**",
        "./assets/js/lib/**",
    ],
});

// Build JS entry points object
const jsEntry = allJsFiles.reduce((entries, file) => {
    const name = path
        .relative("./assets/js", file)
        .replace(/\\/g, "/")
        .replace(/\.js$/, "");
    entries[name] = `./${file}`;
    return entries;
}, {});

// === CSS ENTRY POINTS ===
const cssEntry = {
    "css/admin": "./assets/css/admin.css",
};

// === WEBPACK CONFIGURATION ===
module.exports = [
    // JS Configuration
    {
        name: "js",
        entry: jsEntry,
        output: {
            filename: "assets/js/[name].min.js",
            path: path.resolve(__dirname, "dist/"),
        },
        optimization: {
            minimize: is_minified_js,
            minimizer: [
                new TerserPlugin({
                    test: /\.js$/,
                    exclude: /assets\/lib\//,
                    extractComments: !is_minified_js,
                    terserOptions: {
                        compress: {
                            dead_code: is_minified_js,
                            drop_debugger: is_console_remove,
                            drop_console: is_console_remove,
                            pure_funcs: is_console_remove
                                ? ["console.log", "console.info", "console.warn", "console.error"]
                                : [],
                            conditionals: is_minified_js,
                            evaluate: is_minified_js,
                            loops: is_minified_js,
                            unused: !is_minified_js,
                            toplevel: is_minified_js,
                            hoist_funs: is_minified_js,
                            hoist_vars: is_minified_js,
                            if_return: is_minified_js,
                            join_vars: is_minified_js,
                            collapse_vars: is_minified_js,
                        },
                        mangle: {
                            keep_classnames: is_minified_js,
                            keep_fnames: is_minified_js,
                        },
                        output: {
                            beautify: !is_minified_js,
                            comments: !is_minified_js,
                        },
                    },
                }),
            ],
        },
        module: {
            rules: [
                {
                    test: /\.js$/,
                    exclude: /node_modules/,
                    use: {
                        loader: "babel-loader",
                        options: {
                            presets: ["@babel/preset-env"],
                        },
                    },
                },
            ],
        },
        plugins: [
            new CleanDistFolderPlugin(),
            // Copy library files (no processing)
            new CopyWebpackPlugin({
                patterns: [
                    {
                        from: path.resolve(__dirname, "./assets/lib"),
                        to: path.resolve(__dirname, "dist/assets/lib"),
                        noErrorOnMissing: true,
                    },
                ],
            }),
        ],
        mode: is_minified_js ? "production" : "development",
        devtool: is_minified_js ? false : "source-map",
    },

    // CSS Configuration
    {
        name: "css",
        entry: cssEntry,
        output: {
            path: path.resolve(__dirname, "dist/"),
            library: {
                type: "commonjs2",
            },
        },
        plugins: [
            new MiniCssExtractPlugin({
                filename: (pathData) => `assets/${pathData.chunk.name}.min.css`,
            }),
            new RemoveJsForCssPlugin(),
        ],
        module: {
            rules: [
                {
                    test: /\.css$/,
                    use: [MiniCssExtractPlugin.loader, "css-loader"],
                },
            ],
        },
        optimization: {
            minimize: is_minified_css,
            minimizer: [new CssMinimizerPlugin()],
        },
        mode: is_minified_css ? "production" : "development",
        devtool: false,
    },
];
