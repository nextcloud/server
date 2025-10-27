/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: CC0-1.0
 */

import { createAppConfig } from '@nextcloud/vite-config'
import { resolve } from 'node:path'

const modules = {
	dav: {
		'settings-admin-caldav': resolve(import.meta.dirname, 'apps/dav/src', 'settings-admin.ts'),
		'settings-admin-example-content': resolve(import.meta.dirname, 'apps/dav/src', 'settings-admin-example-content.ts'),
		'settings-personal-availability': resolve(import.meta.dirname, 'apps/dav/src', 'settings-personal-availability.ts'),
	},
	sharebymail: {
		'admin-settings': resolve(import.meta.dirname, 'apps/sharebymail/src', 'settings-admin.ts'),
	},
}

// convert modules to modules entries prefied with the app id
const viteModuleEntries = Object.entries(modules)
	.map(([appId, entries]) => (
		Object.entries(entries)
			.map(([entryName, entryPath]) => [`${appId}-${entryName}`, entryPath])
	))
	.flat(1)

export default createAppConfig(Object.fromEntries(viteModuleEntries), {
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
					entryFileNames: '[name].mjs',
					chunkFileNames: '[name]-[hash].chunk.mjs',
					assetFileNames({ originalFileNames }) {
						const [name] = originalFileNames
						if (name) {
							const [, appId] = name.match(/apps\/([^/]+)\//)!
							return `${appId}-[name]-[hash][extname]`
						}
						return '[name]-[hash][extname]'
					},
					experimentalMinChunkSize: 100 * 1024,
					/* // with rolldown-vite:
					advancedChunks: {
						groups: [
							// one group for common dependencies
							{ name: 'common', test: /[\\/]node_modules[\\/]/ },
							// one group per app with a lower minShareCount to encourage sharing within the app
							...Object.keys(modules).map((name) => ({
								name,
								test: new RegExp(`[\\\\/]apps[\\\\/]${name}[\\\\/]`),
								minShareCount: 2,
							})),
						],
						// only include modules in the groups if they are used at least by 3 different chunks
						minShareCount: 3,
						// only include modules in the groups if they are smaller than 400kb on its own
						// maxModuleSize: 400 * 1024,
						// define the groups output size (not too small but also not too big!)
						minSize: 100 * 1024,
						maxSize: 800 * 1024,
					},
				},
				experimental: {
					strictExecutionOrder: true,
				},
					*/
				},
			},
		},
	},
})
