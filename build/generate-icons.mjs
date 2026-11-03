/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Generates the core icon assets from an icon set sprite.
 *
 * Usage: node build/generate-icons.mjs <path to icon set sprite>
 *
 * The source sprite contains one `<symbol id="i-{name}" viewBox="…">` per icon.
 * Generated files:
 * - core/img/icons/{name}.svg: one file per icon, usable as image source and as `<use href="….svg#i">` target
 * - core/img/icons.svg: sprite containing only the icons listed in SPRITE_ICONS
 * - core/img/icons.json: content hash (used for cache busting) and the list of sprite icons
 */

import { createHash } from 'node:crypto'
import { mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs'
import { join, resolve } from 'node:path'

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

const source = process.argv[2]
if (!source) {
	console.error('Usage: node build/generate-icons.mjs <path to icon set sprite>')
	process.exit(1)
}

const icons = parseSprite(readFileSync(source, 'utf-8'))
const missing = SPRITE_ICONS.filter((name) => !icons.has(name))
if (missing.length > 0) {
	console.error(`Sprite icons missing in the icon set: ${missing.join(', ')}`)
	process.exit(1)
}

const names = [...icons.keys()].sort()
const hash = createHash('sha256')
for (const name of names) {
	const { viewBox, body } = icons.get(name)
	hash.update(`${name}\0${viewBox}\0${body}\0`)
}
hash.update(SPRITE_ICONS.join(','))

rmSync(ICONS_PATH, { recursive: true, force: true })
mkdirSync(ICONS_PATH)
for (const name of names) {
	writeFileSync(join(ICONS_PATH, `${name}.svg`), singleIcon(icons.get(name)))
}

const spriteSymbols = SPRITE_ICONS.map((name) => {
	const { viewBox, body } = icons.get(name)
	return `<symbol id="i-${name}" viewBox="${viewBox}">${body}</symbol>`
})
writeFileSync(join(IMAGE_PATH, 'icons.svg'), `<svg xmlns="http://www.w3.org/2000/svg">${spriteSymbols.join('')}</svg>\n`)
writeFileSync(join(IMAGE_PATH, 'icons.json'), JSON.stringify({
	hash: hash.digest('hex').slice(0, 8),
	sprite: SPRITE_ICONS,
}, null, '\t') + '\n')

console.info(`Generated ${names.length} icons, ${SPRITE_ICONS.length} of them in the sprite`)

/**
 * Parse the symbols of an icon set sprite.
 *
 * @param {string} sprite - The sprite content
 * @return {Map<string, { viewBox: string, body: string }>}
 */
function parseSprite(sprite) {
	const icons = new Map()
	for (const [, attributes, body] of sprite.matchAll(/<symbol\b([^>]*)>(.*?)<\/symbol>/gs)) {
		const id = attributes.match(/\bid="i-([^"]+)"/)?.[1]
		const viewBox = attributes.match(/\bviewBox="([^"]+)"/)?.[1]
		if (!id || !viewBox) {
			throw new Error(`Symbol without id or viewBox: ${attributes}`)
		}
		if (!ICON_NAME.test(id)) {
			throw new Error(`Invalid icon name: ${id}`)
		}
		icons.set(id, { viewBox, body: body.trim() })
	}
	return icons
}

/**
 * Create the single file version of an icon.
 * The outer viewBox must not reuse the symbol viewBox, otherwise its offset is applied twice.
 *
 * @param {{ viewBox: string, body: string }} icon - The icon
 */
function singleIcon({ viewBox, body }) {
	return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20"><symbol id="i" viewBox="${viewBox}">${body}</symbol><use href="#i"/></svg>\n`
}
