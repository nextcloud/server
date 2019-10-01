/* eslint-disable camelcase */
const path = require('path')
const merge = require('webpack-merge')
const { VueLoaderPlugin } = require('vue-loader')

const core = require('./core/webpack')

const accessibility = require('./apps/accessibility/webpack')
const comments = require('./apps/comments/webpack')
const files_sharing = require('./apps/files_sharing/webpack')
const files_trashbin = require('./apps/files_trashbin/webpack')
const files_versions = require('./apps/files_versions/webpack')
const oauth2 = require('./apps/oauth2/webpack')
const settings = require('./apps/settings/webpack')
const systemtags = require('./apps/systemtags/webpack')
const twofactor_backupscodes = require('./apps/twofactor_backupcodes/webpack')
const updatenotifications = require('./apps/updatenotification/webpack')
const workflowengine = require('./apps/workflowengine/webpack')

module.exports = []
	.concat(
		core,
		settings,
		accessibility,
		comments,
		files_sharing,
		files_trashbin,
		files_versions,
		oauth2,
		systemtags,
		twofactor_backupscodes,
		updatenotifications,
		workflowengine
	)
	.map(config => merge.smart({
		module: {
			rules: [
				{
					test: /\.css$/,
					use: ['vue-style-loader', 'css-loader']
				},
				{
					test: /\.scss$/,
					use: ['vue-style-loader', 'css-loader', 'sass-loader']
				},
				{
					test: /\.(js|vue)$/,
					loader: 'eslint-loader',
					// no checks against vendors, modules or handlebar compiled files
					exclude: /node_modules|vendor|templates\.js/,
					enforce: 'pre',
					options: {
						// we cannot simply use the eslint binary as we
						// don't want to parse all the js files so let's
						// use it from within webpack and only check
						// against our compiled files
						fix: process.env.ESLINT_FIX === 'true'
					}
				},
				{
					test: /\.vue$/,
					loader: 'vue-loader',
					exclude: /node_modules/
				},
				{
					test: /\.js$/,
					loader: 'babel-loader',
					exclude: /node_modules/
				},
				{
					test: /\.(png|jpg|gif)$/,
					loader: 'url-loader',
					options: {
						name: '[name].[ext]?[hash]',
						limit: 8192
					}
				},
				{
					test: /\.handlebars/,
					loader: 'handlebars-loader',
					query: {
						extensions: '.handlebars'
					}
				}

			]
		},
		plugins: [new VueLoaderPlugin()],
		resolve: {
			alias: {
				OC: path.resolve(__dirname, './core/src/OC'),
				OCA: path.resolve(__dirname, './core/src/OCA'),
				// make sure to use the handlebar runtime when importing
				handlebars: 'handlebars/runtime'
			},
			extensions: ['*', '.js', '.vue']
		}
	}, config))
