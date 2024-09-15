import { createAppConfig } from '@nextcloud/vite-config'
import { readFileSync } from 'node:fs'
import { join } from 'node:path'

const isProduction = process.env.NODE_ENV === 'production'
const plyrIcons = readFileSync(
	join(__dirname, 'node_modules', 'plyr', 'dist', 'plyr.svg'),
	{ encoding: 'utf8' },
)

export default createAppConfig(
	{
		main: 'src/main.js',
		init: 'src/init.ts',
	},
	{
		replace: {
			PLYR_ICONS: JSON.stringify(plyrIcons),
		},
		minify: isProduction,
		// ensure that every JS entry point has a matching CSS file
		createEmptyCSSEntryPoints: true,
		// Make sure we also clear the CSS directory
		emptyOutputDirectory: {
			additionalDirectories: ['css'],
		},
	},
)
