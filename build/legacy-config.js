/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const isDev = process.env.NODE_ENV === 'development'

export default {
	mode: isDev ? 'development' : 'production',
	devtool: isDev ? 'cheap-source-map' : 'source-map',
	module: {
		rules: [
			{
				test: /\.vue$/,
				loader: 'vue-loader',
				options: {
					experimentalInlineMatchResource: true,
				},
			},
			{
				test: /\.(png|jpe?g|gif|svg|woff2?|eot|ttf)$/,
				type: 'asset/inline',
			},
			{
				resourceQuery: /\?raw$/,
				type: 'asset/source',
			},
			{
				test: /\.css$/,
				use: ['vue-style-loader', 'css-loader'],
				type: 'javascript/auto',
			},
			{
				test: /\.scss$/,
				use: ['vue-style-loader', 'css-loader', 'sass-loader'],
				type: 'javascript/auto',
			},
			{
				test: /\.ts$/, // add this rule when you use TypeScript in Vue SFC
				loader: 'builtin:swc-loader',
				options: {
					jsc: {
						parser: {
							syntax: 'typescript',
						},
					},
				},
				type: 'javascript/auto',
			},
		],
	},
	optimization: {
		splitChunks: {
			automaticNameDelimiter: '-',
			minChunks: 3, // minimum number of chunks that must share the module
			cacheGroups: {
				vendors: {
					// split every dependency into one bundle
					test: /[\\/]node_modules[\\/]/,
					// necessary to keep this name to properly inject it
					// see OC_Template.php
					name: 'core-common',
					chunks: 'all',
				},
			},
		},
	},
	externals: {
		OC: 'OC',
		OCA: 'OCA',
		OCP: 'OCP',
	},
	experiments: {
		css: false,
	},
}
