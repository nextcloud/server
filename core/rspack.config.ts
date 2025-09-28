/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineConfig } from '@rspack/cli'
import { ProvidePlugin } from '@rspack/core'
import NodePolyfillPlugin from 'node-polyfill-webpack-plugin'
import { join } from 'path'
import { VueLoaderPlugin } from 'vue-loader'
import defaultConfig from '../build/legacy-config.js'

const jQuery = await import.meta.resolve('jquery')

export default defineConfig({
	...defaultConfig,
	plugins: [
		new ProvidePlugin({
			// Provide jQuery to jquery plugins as some are loaded before $ is exposed globally.
			// We need to provide the path to node_moduels as otherwise npm link will fail due
			// to tribute.js checking for jQuery in @nextcloud/vue
			jQuery,
		}),
		new VueLoaderPlugin(),
		new NodePolyfillPlugin({
			additionalAliases: ['process'],
		}),
	],
	output: {
		path: join(import.meta.dirname, '../dist/'),
		chunkFilename: 'chunk-core__[name].js',
		clean: true,
	},
	entry: {
		'core-ajax-cron': join(import.meta.dirname, 'src/ajax-cron.ts'),
		'core-files_client': join(import.meta.dirname, 'src/files/client.js'),
		'core-files_fileinfo': join(import.meta.dirname, 'src/files/fileinfo.js'),
		'core-install': join(import.meta.dirname, 'src/install.ts'),
		'core-login': join(import.meta.dirname, 'src/login.js'),
		'core-main': join(import.meta.dirname, 'src/main.js'),
		'core-maintenance': join(import.meta.dirname, 'src/maintenance.js'),
		'core-public-page-menu': join(import.meta.dirname, 'src/public-page-menu.ts'),
		'core-public-page-user-menu': join(import.meta.dirname, 'src/public-page-user-menu.ts'),
		'core-recommendedapps': join(import.meta.dirname, 'src/recommendedapps.js'),
		'core-unified-search': join(import.meta.dirname, 'src/unified-search.ts'),
		'core-legacy-unified-search': join(import.meta.dirname, 'src/legacy-unified-search.js'),
		'core-unsupported-browser': join(import.meta.dirname, 'src/unsupported-browser.js'),
		'core-unsupported-browser-redirect': join(import.meta.dirname, 'src/unsupported-browser-redirect.js'),
		'core-public': join(import.meta.dirname, 'src/public.ts'),
		'core-twofactor-request-token': join(import.meta.dirname, 'src/twofactor-request-token.ts'),
	},
})
