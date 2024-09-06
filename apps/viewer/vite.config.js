import { createAppConfig } from '@nextcloud/vite-config'
import { readFileSync } from 'node:fs'
import { join } from 'node:path'

const isProduction = process.env.NODE_ENV === 'production'
const plyrIcons = readFileSync(join(__dirname, 'node_modules', 'plyr', 'dist', 'plyr.svg'), { encoding: 'utf8' })

export default createAppConfig({
	main: 'src/main.js',
}, {
	replace: {
		PLYR_ICONS: JSON.stringify(plyrIcons),
	},
	minify: isProduction,
})
