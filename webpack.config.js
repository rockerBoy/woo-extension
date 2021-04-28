const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');

module.exports = {
    mode: "development",
    entry: [
        './src/assets/js/index.js'
    ],
    output: {
        path: `${__dirname}/assets/js`,
        filename: 'bundle.js'
    },
    optimization: {
        minimize: true,
        minimizer: [
            new CssMinimizerPlugin(),
        ]
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: 'style.css'
        })
    ],
    resolve: { extensions: ['.js'] },
    module: {
        rules: [
            {
                test: '/\.js$/',
                exclude: /node_modules/,
                loader: 'babel-loader'
            },
            {
                test: '/\.css/',
                use: [ MiniCssExtractPlugin.loader, 'css-loader']
            },
            {
                test: /\.s[ac]ss$/i,
                use: ['style-loader', 'css-loader', 'sass-loader']
            }
        ]
    },
}