const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';

  return {
	entry: {
	  'main': './src/main.js',
	  // 'admin': './src/admin.js'
	},
	output: {
	  path: path.resolve(__dirname, 'assets/react'),
	  filename: isProduction ? '[name].min.js' : '[name].js',
	  sourceMapFilename: '[name].js.map'
	},
	devtool: isProduction ? 'source-map' : 'eval-source-map',
	module: {
	  rules: [
		{
		  test: /\.js$/,
		  exclude: /node_modules/,
		  use: {
			loader: 'babel-loader',
			options: {
			  presets: ['@babel/preset-env', '@babel/preset-react']
			}
		  }
		},
		{
		  test: /\.scss$/,
		  use: [
			MiniCssExtractPlugin.loader,
			'css-loader',
			'sass-loader'
		  ]
		}
	  ]
	},
	plugins: [
	  new MiniCssExtractPlugin({
		filename: isProduction ? '[name].min.css' : '[name].css'
	  })
	],
	optimization: {
	  minimize: isProduction,
	  minimizer: [
		new TerserPlugin({
		  terserOptions: {
			sourceMap: true
		  }
		}),
		new CssMinimizerPlugin()
	  ]
	}
  };
};