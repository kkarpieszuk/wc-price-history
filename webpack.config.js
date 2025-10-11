const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  entry: {
	// Dodaj tutaj punkty wejścia dla swoich komponentów
	'main': './src/main.js',
	// 'admin': './src/admin.js'
  },
  output: {
	path: path.resolve(__dirname, 'assets/react'),
	filename: '[name].js'
  },
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
	  filename: '[name].css'
	})
  ]
};