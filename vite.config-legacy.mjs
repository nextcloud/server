// This is creating the legacy chunk for apps that still use jQuery & Handlebars UI
import { join } from 'path'
import { defineConfig } from 'vite'

export default defineConfig({
	build: {
		// inline assets of jQuery UI
		assetsInlineLimit: 9 * 8 * 1024,
		// We run this after the main build, so do not remove the built assets
		emptyOutDir: false,
		outDir: 'dist',
		rollupOptions: {
			input: {
				// eslint-disable-next-line no-undef
				'core-legacy': join(__dirname, 'core', 'src', 'legacy.js'),
			},
			output: {
				format: 'iife',
				entryFileNames: '[name].js',
			},
		},
	},
})
