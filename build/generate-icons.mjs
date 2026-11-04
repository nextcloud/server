/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Generates the core icon assets from the @nextcloud/icons package.
 *
 * Generated files:
 * - core/img/icons/{name}.svg: the icon files of the package, plus a copy for every alias
 * - core/img/icons.svg: sprite containing only the icons listed in SPRITE_ICONS
 * - core/img/icons.json: content hash (used for cache busting) and the list of sprite icons
 */

import { createHash } from 'node:crypto'
import { copyFileSync, mkdirSync, readdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs'
import { createRequire } from 'node:module'
import { dirname, join, resolve } from 'node:path'

const IMAGE_PATH = resolve(import.meta.dirname, '../core/img')
const ICONS_PATH = join(IMAGE_PATH, 'icons')
const ICON_NAME = /^[a-z0-9_]{1,64}$/

/**
 * Icons bundled into the sprite, all other icons are only available as single files.
 */
const SPRITE_ICONS = [
	'add',
	'arrow_back',
	'arrow_forward',
	'calendar_today',
	'chat',
	'check',
	'chevron_left',
	'chevron_right',
	'close',
	'content_copy',
	'delete',
	'download',
	'edit',
	'error',
	'expand_less',
	'expand_more',
	'filter_list',
	'folder',
	'group',
	'home',
	'info',
	'link',
	'lock',
	'logout',
	'mail',
	'menu',
	'more_vert',
	'notifications',
	'open_in_new',
	'person',
	'refresh',
	'search',
	'settings',
	'share',
	'sort',
	'star',
	'star_filled',
	'upload',
	'visibility',
	'warning',
]

const packagePath = dirname(createRequire(import.meta.url).resolve('@nextcloud/icons/package.json'))
const sourcePath = join(packagePath, 'icons')
const aliases = JSON.parse(readFileSync(join(packagePath, 'aliases.json'), 'utf-8'))

const icons = readdirSync(sourcePath)
	.filter((file) => file.endsWith('.svg'))
	.map((file) => file.slice(0, -4))
	.sort()
for (const name of [...icons, ...Object.keys(aliases)]) {
	if (!ICON_NAME.test(name)) {
		throw new Error(`Invalid icon name: ${name}`)
	}
}
for (const [alias, name] of Object.entries(aliases)) {
	if (icons.includes(alias) || !icons.includes(name)) {
		throw new Error(`Invalid alias ${alias} for ${name}`)
	}
}

const missing = SPRITE_ICONS.filter((name) => !icons.includes(name))
if (missing.length > 0) {
	console.error(`Sprite icons missing in the icon set: ${missing.join(', ')}`)
	process.exit(1)
}

rmSync(ICONS_PATH, { recursive: true, force: true })
mkdirSync(ICONS_PATH)
const files = new Map()
for (const name of icons) {
	copyFileSync(join(sourcePath, `${name}.svg`), join(ICONS_PATH, `${name}.svg`))
	files.set(name, `${name}.svg`)
}
for (const [alias, name] of Object.entries(aliases)) {
	copyFileSync(join(sourcePath, `${name}.svg`), join(ICONS_PATH, `${alias}.svg`))
	files.set(alias, `${name}.svg`)
}

const hash = createHash('sha256')
for (const [name, file] of [...files].sort(([a], [b]) => a.localeCompare(b))) {
	hash.update(`${name}\0`)
	hash.update(readFileSync(join(sourcePath, file)))
}
hash.update(SPRITE_ICONS.join(','))

const spriteSymbols = SPRITE_ICONS.map((name) => {
	const icon = readFileSync(join(sourcePath, `${name}.svg`), 'utf-8')
	const symbol = icon.match(/<symbol id="i" (viewBox="[^"]+")>(.*?)<\/symbol>/s)
	if (!symbol) {
		throw new Error(`Icon ${name} has no symbol`)
	}
	return `<symbol id="i-${name}" ${symbol[1]}>${symbol[2]}</symbol>`
})
writeFileSync(join(IMAGE_PATH, 'icons.svg'), `<svg xmlns="http://www.w3.org/2000/svg">${spriteSymbols.join('')}</svg>\n`)
writeFileSync(join(IMAGE_PATH, 'icons.json'), JSON.stringify({
	hash: hash.digest('hex').slice(0, 8),
	sprite: SPRITE_ICONS,
}, null, '\t') + '\n')

console.info(`Generated ${files.size} icons (${Object.keys(aliases).length} aliases), ${SPRITE_ICONS.length} of them in the sprite`)
