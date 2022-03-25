/**
 * @copyright Copyright (c) 2016 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Christopher Ng <chrng8@gmail.com>
 * @author Jan C. Borchardt <hey@jancborchardt.net>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

const path = require('path')

// TODO use @nextcloud/webpack-vue-config
module.exports = {
	module: {
		rules: [
			{
				test: /\.(png|jpe?g|gif|svg|woff2?|eot|ttf)$/,
				loader: 'url-loader',
				options: {
					name: '[name].[ext]?[hash]',
				},
			},
		]
	},
	entry: {
		'settings-admin-basic-settings': path.join(__dirname, 'src', 'main-admin-basic-settings'),
		'settings-apps-users-management': path.join(__dirname, 'src', 'main-apps-users-management'),
		'settings-admin-security': path.join(__dirname, 'src', 'main-admin-security'),
		'settings-admin-delegation': path.join(__dirname, 'src', 'main-admin-delegation'),
		'settings-personal-security': path.join(__dirname, 'src', 'main-personal-security'),
		'settings-personal-webauthn': path.join(__dirname, 'src', 'main-personal-webauth'),
		'settings-nextcloud-pdf': path.join(__dirname, 'src', 'main-nextcloud-pdf'),
		'settings-personal-info': path.join(__dirname, 'src', 'main-personal-info'),
	},
	output: {
		path: path.resolve(__dirname, './js'),
		publicPath: '/js/',
		filename: 'vue-[name].js?v=[contenthash]',
		chunkFilename: 'vue-[name].js?v=[contenthash]',
		jsonpFunction: 'webpackJsonpSettings',
	},
	optimization: {
		splitChunks: {
			automaticNameDelimiter: '-',
		},
	},
}
