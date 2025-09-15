const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');

module.exports = {
  context: __dirname,
  entry: {
    front: ['./js/front.js', './scss/front.scss'],
    admin:  ['./js/admin.js',  './scss/admin.scss']
  },
  output: {
    path: path.resolve(__dirname, '../views'),     // ← plus de dist
    filename: 'js/[name].js',                  // ← noms fixes
    clean: false,                                  // on évite d’effacer views/*
    publicPath: ''
  },
  module: {
    rules: [
      { test: /\.js$/i, exclude: /node_modules/,
        use: { loader: 'babel-loader',
          options: { presets: [['@babel/preset-env', { useBuiltIns: 'usage', corejs: 3 }]] } }
      },
      { test: /\.(scss|css)$/i, use: [MiniCssExtractPlugin.loader, 'css-loader', 'postcss-loader', 'sass-loader'] }
    ]
  },
  plugins: [
    new MiniCssExtractPlugin({ filename: 'css/[name].css' }) // ← noms fixes
  ],
  optimization: {
    minimizer: [new TerserPlugin({ extractComments: false }), new CssMinimizerPlugin()]
  }
};
