import { createAppConfig } from '@nextcloud/vite-config'
import entrypoints from './server.modules.mjs'
import { defineConfig } from 'vite'

const entries = Object.entries(entrypoints).map(([prefix, modules]) => Object.fromEntries(
	Object.entries(modules).map(([name, source]) => [`${prefix}-${name}`, source]),
)).reduce((p, c) => ({ ...p, ...c }), {})

export default defineConfig(async (env) => {

	const config = await createAppConfig(
		entries,
		{
			// Split CSS for JS compiler performance (browser)
			inlineCSS: false,
			// In contrast to apps, we really output everything to "dist"
			// so we need to overwrite the assets filenames to prevent "dist/js/name.mjs"
			assetFileNames: (info) => `${info.name}`,
			// BOM for licenses
			thirdPartyLicense: 'dist/vendor.LICENSE.txt',
			// This is app specific, we do not use js/ as the output directory
			emptyOutputDirectory: false,
			config: {
				// Relative base to output (dist)
				base: '',
				plugins: [
				// custom plugin to optimize dynamic chunks by setting guranteed loading order
					{
						moduleParsed(info) {
							if (info.isEntry && info.id === entries['core-main']) {
							// every other module is implicitly loaded AFTER `core-main` this helps with optimization of dynamic chunks
								Object.values(entries).forEach(i => i !== info.id ? info.implicitlyLoadedBefore.push(i) : undefined)
							}
						},
					},
				],

				build: {
					// All output goes to "dist" for server
					outDir: 'dist',
					emptyOutDir: true,
					// Create only one chunk of splitted CSS instead of multiple per entry point
					cssCodeSplit: false,
					rollupOptions: {
						output: {
							// Keep core-common files together to reduce number of chunks
							chunkFileNames: (info) => info.name.match(/core-common/) ? 'core-common.mjs' : 'chunks/[name]-[hash].mjs',
							// Keep entry names as is
							entryFileNames: '[name].mjs',
							// vue components should go all into core-common, icons should go into "icons" chunk
							manualChunks: (id) => id.match(/@nextcloud\/vue(\/|$)/) ? 'core-common' : (id.match(/(mdi|vue-mater)/) ? 'icons' : null),
						},
					},
				},
			},
		})(env)

	// Unset the module loading function as window.OC is not set yet
	delete config.experimental.renderBuiltUrl
	return config
})
