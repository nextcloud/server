/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: CC0-1.0
 */

import { createAppConfig } from '@nextcloud/vite-config'
import { join, resolve } from 'node:path'

export default createAppConfig({
	'admin-settings': join(import.meta.dirname, 'apps/sharebymail/src', 'settings-admin.ts'),
}, {
	emptyOutputDirectory: false,
	config: {
		root: resolve(__dirname, '../..'),
		resolve: {
			preserveSymlinks: true,
		},
		build: {
			rolldownOptions: {
				output: {
					entryFileNames({ facadeModuleId }) {
						const [, appId] = facadeModuleId!.match(/apps\/([^/]+)\//)!
						return `dist/${appId}-[name].mjs`
					},
					chunkFileNames: 'dist/[name]-[hash].chunk.mjs',
					assetFileNames({ originalFileNames }) {
						const [name] = originalFileNames
						if (name) {
							const [, appId] = name.match(/apps\/([^/]+)\//)!
							return `dist/${appId}-[name]-[hash][extname]`
						}
						return 'dist/[name]-[hash][extname]'
					},
					/*advancedChunks: {
						groups: [{ name: 'common', test: /[\\/]node_modules[\\/]/ }],
						minSize: 20 * 1024,
						maxSize: 500 * 1024,
					},*/
				},
			},
		},
	},
})
