import { createAppConfig } from '@nextcloud/vite-config'
import { readFileSync } from 'node:fs'
import { join } from 'node:path'

const isTesting = !!process.env.TESTING
const plyrIcons = readFileSync(join(__dirname, 'node_modules', 'plyr', 'dist', 'plyr.svg'), { encoding: 'utf8' })

export default createAppConfig({
	main: 'src/main.js',
}, {
	replace: {
		PLYR_ICONS: JSON.stringify(plyrIcons),
		INJECT_CYPRESS_FONT: isTesting ? '; import("@fontsource/roboto");' : '',
	},
	minify: false,
})
