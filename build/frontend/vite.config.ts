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
	federatedfilesharing: {
		'init-files': resolve(import.meta.dirname, 'apps/federatedfilesharing/src', 'init-files.js'),
		'settings-admin': resolve(import.meta.dirname, 'apps/federatedfilesharing/src', 'settings-admin.ts'),
		'settings-personal': resolve(import.meta.dirname, 'apps/federatedfilesharing/src', 'settings-personal.ts'),
	},
	files_external: {
		init: resolve(import.meta.dirname, 'apps/files_external/src', 'init.ts'),
		settings: resolve(import.meta.dirname, 'apps/files_external/src', 'settings.js'),
	},
	files_reminders: {
		init: resolve(import.meta.dirname, 'apps/files_reminders/src', 'files-init.ts'),
	},
	files_trashbin: {
		init: resolve(import.meta.dirname, 'apps/files_trashbin/src', 'files-init.ts'),
	},
	files_versions: {
		'sidebar-tab': resolve(import.meta.dirname, 'apps/files_versions/src', 'sidebar_tab.ts'),
	},
	oauth2: {
		'settings-admin': resolve(import.meta.dirname, 'apps/oauth2/src', 'settings-admin.ts'),
	},
	sharebymail: {
		'admin-settings': resolve(import.meta.dirname, 'apps/sharebymail/src', 'settings-admin.ts'),
	},
	theming: {
		'settings-personal': resolve(import.meta.dirname, 'apps/theming/src', 'settings-personal.ts'),
		'settings-admin': resolve(import.meta.dirname, 'apps/theming/src', 'settings-admin.ts'),
	},
	twofactor_backupcodes: {
		'settings-personal': resolve(import.meta.dirname, 'apps/twofactor_backupcodes/src', 'settings-personal.ts'),
	},
	user_ldap: {
		'settings-admin': resolve(import.meta.dirname, 'apps/user_ldap/src', 'settings-admin.ts'),
	},
	user_status: {
		menu: resolve(import.meta.dirname, 'apps/user_status/src', 'menu.js'),
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
		experimental: {
			renderBuiltUrl(filename, { hostType }) {
				if (hostType === 'css') {
					return `./${filename}`
				}
				return {
					runtime: `window.OC.filePath('', '', 'dist/${filename}')`,
				}
			},
		},
	},
})
