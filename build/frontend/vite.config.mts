/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: CC0-1.0
 */

import { createAppConfig } from '@nextcloud/vite-config'
import { resolve } from 'node:path'

export default createAppConfig({
	'admin-settings': resolve(import.meta.dirname, 'apps/sharebymail/src', 'settings-admin.ts'),
}, {
	emptyOutputDirectory: {
		additionalDirectories: [resolve(import.meta.dirname, '../..', 'dist')],
	},
	extractLicenseInformation: {
		includeSourceMaps: true,
	},
	config: {
		root: resolve(import.meta.dirname, '../..'),
		resolve: {
			preserveSymlinks: true,
		},
		build: {
			outDir: 'dist',
			rollupOptions: {
				output: {
					entryFileNames({ facadeModuleId }) {
						const [, appId] = facadeModuleId!.match(/apps\/([^/]+)\//)!
						return `${appId}-[name].mjs`
					},
					chunkFileNames: '[name]-[hash].chunk.mjs',
					assetFileNames({ originalFileNames }) {
						const [name] = originalFileNames
						if (name) {
							const [, appId] = name.match(/apps\/([^/]+)\//)!
							return `${appId}-[name]-[hash][extname]`
						}
						return '[name]-[hash][extname]'
					},
					/* advancedChunks: {
						groups: [{ name: 'common', test: /[\\/]node_modules[\\/]/ }],
						// only include modules in the groups if they are used at least by 3 different chunks
						minShareCount: 3,
						// only include modules in the groups if they are smaller than 200kb on its own
						maxModuleSize: 200 * 1024,
						// define the groups output size (not too small but also not too big!)
						minSize: 50 * 1024,
						maxSize: 500 * 1024,
					}, */
				},
			},
		},
	},
})
