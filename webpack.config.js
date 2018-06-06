const path = require('path');

const dev = process.env.NODE_ENV === "development";

const webpack = require("webpack");
const ManifestPlugin = require('webpack-manifest-plugin');
const CleanWebpackPlugin = require('clean-webpack-plugin');
const ExtractTextPlugin = require("extract-text-webpack-plugin");
const extractSass = new ExtractTextPlugin({
    filename: "[name].[chunkhash].css",
    disable: dev
});

let cssLoaders = [
    {
        loader: 'css-loader',
        options: {
            sourceMap: true
        }
    },
    {
        loader: 'resolve-url-loader',
        options: {
            root: path.resolve(__dirname, 'node_modules')
        }
    }
];

if (!dev) {
    cssLoaders.push({
        loader: 'postcss-loader',
        options: {
            plugins: (loader) => [
                require('autoprefixer')({
                    browsers: ['last 2 versions', 'ie > 8']
                })
            ],
            sourceMap: true
        }
    });
}

cssLoaders.push({
    loader: 'sass-loader',
    options: {
        sourceMap: true
    }
});

module.exports = {
    entry: {
        'theme': './app/theme/scss/theme.scss',
        'app': './app/theme/script/theme.js',
    },
    output: {
        path: path.resolve(__dirname, 'public/dist/'),
        filename: "[name].[chunkhash].js"
    },
    module: {
        rules: [
            {
                test: /\.js/,
                exclude: /(node_modules)/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['env']
                    }
                }
            },
            {
                test: /\.scss$/,
                use: extractSass.extract({
                    use: cssLoaders,
                    fallback: "style-loader"
                })
            },
            {
                test: /\.css$/,
                use: {
                    loader: 'css-loader'
                }
            },
            {
                test: /\.(woff|ttf|otf|eot|woff2|jpg|png|svg)$/,
                use: [
                    { loader: "file-loader" }
                ]
            }

        ]
    },
    plugins: [
        extractSass,
        new ManifestPlugin(),
        new CleanWebpackPlugin('dist', {
            root: path.resolve(__dirname, 'public/dist/'),
            dry: false
        }),
        new webpack.ProvidePlugin({
            "$": "jquery",
            "jQuery": "jquery",
            "Popper": "popper.js"
        })
    ]
};