/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { readFileSync, writeFileSync} from 'node:fs'
import { join, resolve } from 'node:path'
import { compileString } from 'sass'

const IMAGE_PATH = resolve(import.meta.dirname, '../core/img')

const colors = {
	dark: '000',
	white: 'fff',
	// gold but for backwards compatibility called yellow
	yellow: 'a08b00',
	red: 'e9322d',
	orange: 'eca700',
	green: '46ba61',
	grey: '969696',
}

const variables = {}
const icons = {
	add: join(IMAGE_PATH, 'actions', 'add.svg'),
	address: join(IMAGE_PATH, 'actions', 'address.svg'),
	'alert-outline': join(IMAGE_PATH, 'actions', 'alert-outline.svg'),
	'audio-off': join(IMAGE_PATH, 'actions', 'audio-off.svg'),
	audio: join(IMAGE_PATH, 'actions', 'audio.svg'),
	calendar: join(IMAGE_PATH, 'places', 'calendar.svg'),
	caret: join(IMAGE_PATH, 'actions', 'caret.svg'),
	'category-app-bundles': join(IMAGE_PATH, 'categories', 'bundles.svg'),
	'category-auth': join(IMAGE_PATH, 'categories', 'auth.svg'),
	'category-customization': join(IMAGE_PATH, 'categories', 'customization.svg'),
	'category-dashboard': join(IMAGE_PATH, 'categories', 'dashboard.svg'),
	'category-files': join(IMAGE_PATH, 'categories', 'files.svg'),
	'category-games': join(IMAGE_PATH, 'categories', 'games.svg'),
	'category-integration': join(IMAGE_PATH, 'categories', 'integration.svg'),
	'category-monitoring': join(IMAGE_PATH, 'categories', 'monitoring.svg'),
	'category-multimedia': join(IMAGE_PATH, 'categories', 'multimedia.svg'),
	'category-office': join(IMAGE_PATH, 'categories', 'office.svg'),
	'category-organization': join(IMAGE_PATH, 'categories', 'organization.svg'),
	'category-social': join(IMAGE_PATH, 'categories', 'social.svg'),
	'category-workflow': join(IMAGE_PATH, 'categories', 'workflow.svg'),
	change: join(IMAGE_PATH, 'actions', 'change.svg'),
	checkmark: join(IMAGE_PATH, 'actions', 'checkmark.svg'),
	circles: join(IMAGE_PATH, 'apps', 'circles.svg'),
	clippy: join(IMAGE_PATH, 'actions', 'clippy.svg'),
	close: join(IMAGE_PATH, 'actions', 'close.svg'),
	comment: join(IMAGE_PATH, 'actions', 'comment.svg'),
	'confirm-fade': join(IMAGE_PATH, 'actions', 'confirm-fade.svg'),
	confirm: join(IMAGE_PATH, 'actions', 'confirm.svg'),
	contacts: join(IMAGE_PATH, 'places', 'contacts.svg'),
	delete: join(IMAGE_PATH, 'actions', 'delete.svg'),
	desktop: join(IMAGE_PATH, 'clients', 'desktop.svg'),
	details: join(IMAGE_PATH, 'actions', 'details.svg'),
	'disabled-user': join(IMAGE_PATH, 'actions', 'disabled-user.svg'),
	'disabled-users': join(IMAGE_PATH, 'actions', 'disabled-users.svg'),
	download: join(IMAGE_PATH, 'actions', 'download.svg'),
	edit: join(IMAGE_PATH, 'actions', 'edit.svg'),
	encryption: resolve(import.meta.dirname, '../apps/files_external/img', 'app.svg'),
	error: join(IMAGE_PATH, 'actions', 'error.svg'),
	external: join(IMAGE_PATH, 'actions', 'external.svg'),
	favorite: join(IMAGE_PATH, 'actions', 'star-dark.svg'),
	files: join(IMAGE_PATH, 'places', 'files.svg'),
	filter: join(IMAGE_PATH, 'actions', 'filter.svg'),
	folder: join(IMAGE_PATH, 'filetypes', 'folder.svg'),
	fullscreen: join(IMAGE_PATH, 'actions', 'fullscreen.svg'),
	group: join(IMAGE_PATH, 'actions', 'group.svg'),
	history: join(IMAGE_PATH, 'actions', 'history.svg'),
	home: join(IMAGE_PATH, 'places', 'home.svg'),
	info: join(IMAGE_PATH, 'actions', 'info.svg'),
	link: join(IMAGE_PATH, 'places', 'link.svg'),
	logout: join(IMAGE_PATH, 'actions', 'logout.svg'),
	mail: join(IMAGE_PATH, 'actions', 'mail.svg'),
	'menu-sidebar': join(IMAGE_PATH, 'actions', 'menu-sidebar.svg'),
	menu: join(IMAGE_PATH, 'actions', 'menu.svg'),
	more: join(IMAGE_PATH, 'actions', 'more.svg'),
	music: join(IMAGE_PATH, 'places', 'music.svg'),
	password: join(IMAGE_PATH, 'actions', 'password.svg'),
	pause: join(IMAGE_PATH, 'actions', 'pause.svg'),
	phone: join(IMAGE_PATH, 'clients', 'phone.svg'),
	picture: join(IMAGE_PATH, 'places', 'picture.svg'),
	'play-add': join(IMAGE_PATH, 'actions', 'play-add.svg'),
	'play-next': join(IMAGE_PATH, 'actions', 'play-next.svg'),
	'play-previous': join(IMAGE_PATH, 'actions', 'play-previous.svg'),
	play: join(IMAGE_PATH, 'actions', 'play.svg'),
	projects: join(IMAGE_PATH, 'actions', 'projects.svg'),
	public: join(IMAGE_PATH, 'actions', 'public.svg'),
	quota: join(IMAGE_PATH, 'actions', 'quota.svg'),
	recent: join(IMAGE_PATH, 'actions', 'recent.svg'),
	rename: join(IMAGE_PATH, 'actions', 'rename.svg'),
	'screen-off': join(IMAGE_PATH, 'actions', 'screen-off.svg'),
	screen: join(IMAGE_PATH, 'actions', 'screen.svg'),
	search: join(IMAGE_PATH, 'actions', 'search.svg'),
	settings: join(IMAGE_PATH, 'actions', 'settings-dark.svg'),
	share: join(IMAGE_PATH, 'actions', 'share.svg'),
	shared: join(IMAGE_PATH, 'actions', 'share.svg'),
	'sound-off': join(IMAGE_PATH, 'actions', 'sound-off.svg'),
	sound: join(IMAGE_PATH, 'actions', 'sound.svg'),
	star: join(IMAGE_PATH, 'actions', 'star.svg'),
	starred: join(IMAGE_PATH, 'actions', 'star-dark.svg'),
	'star-rounded': join(IMAGE_PATH, 'actions', 'star-rounded.svg'),
	tablet: join(IMAGE_PATH, 'clients', 'tablet.svg'),
	tag: join(IMAGE_PATH, 'actions', 'tag.svg'),
	talk: join(IMAGE_PATH, 'apps', 'spreed.svg'),
	teams: join(IMAGE_PATH, 'apps', 'circles.svg'),
	'template-add': join(IMAGE_PATH, 'actions', 'template-add.svg'),
	timezone: join(IMAGE_PATH, 'actions', 'timezone.svg'),
	'toggle-background': join(IMAGE_PATH, 'actions', 'toggle-background.svg'),
	'toggle-filelist': join(IMAGE_PATH, 'actions', 'toggle-filelist.svg'),
	'toggle-pictures': join(IMAGE_PATH, 'actions', 'toggle-pictures.svg'),
	toggle: join(IMAGE_PATH, 'actions', 'toggle.svg'),
	'triangle-e': join(IMAGE_PATH, 'actions', 'triangle-e.svg'),
	'triangle-n': join(IMAGE_PATH, 'actions', 'triangle-n.svg'),
	'triangle-s': join(IMAGE_PATH, 'actions', 'triangle-s.svg'),
	unshare: join(IMAGE_PATH, 'actions', 'unshare.svg'),
	upload: join(IMAGE_PATH, 'actions', 'upload.svg'),
	'user-admin': join(IMAGE_PATH, 'actions', 'user-admin.svg'),
	user: join(IMAGE_PATH, 'actions', 'user.svg'),
	'video-off': join(IMAGE_PATH, 'actions', 'video-off.svg'),
	'video-switch': join(IMAGE_PATH, 'actions', 'video-switch.svg'),
	video: join(IMAGE_PATH, 'actions', 'video.svg'),
	'view-close': join(IMAGE_PATH, 'actions', 'view-close.svg'),
	'view-download': join(IMAGE_PATH, 'actions', 'view-download.svg'),
	'view-next': join(IMAGE_PATH, 'actions', 'arrow-right.svg'),
	'view-pause': join(IMAGE_PATH, 'actions', 'view-pause.svg'),
	'view-play': join(IMAGE_PATH, 'actions', 'view-play.svg'),
	'view-previous': join(IMAGE_PATH, 'actions', 'arrow-left.svg'),
}

const iconsColor = {
	'add-folder-description': {
		path: join(IMAGE_PATH, 'actions', 'add-folder-description.svg'),
		color: 'grey',
	},
	settings: {
		path: join(IMAGE_PATH, 'actions', 'settings.svg'),
		color: 'black',
	},
	'error-color': {
		path: join(IMAGE_PATH, 'actions', 'error.svg'),
		color: 'red',
	},
	'checkmark-color': {
		path: join(IMAGE_PATH, 'actions', 'checkmark.svg'),
		color: 'green',
	},
	starred: {
		path: join(IMAGE_PATH, 'actions', 'star-dark.svg'),
		color: 'yellow',
	},
	star: {
		path: join(IMAGE_PATH, 'actions', 'star-dark.svg'),
		color: 'grey',
	},
	'delete-color': {
		path: join(IMAGE_PATH, 'actions', 'delete.svg'),
		color: 'red',
	},
	file: {
		path: join(IMAGE_PATH, 'filetypes', 'text.svg'),
		color: 'grey',
	},
	'filetype-file': {
		path: join(IMAGE_PATH, 'filetypes', 'file.svg'),
		color: 'grey',
	},
	'filetype-folder': {
		path: join(IMAGE_PATH, 'filetypes', 'folder.svg'),
		// TODO: replace primary ?
		color: 'primary',
	},
	'filetype-folder-drag-accept': {
		path: join(IMAGE_PATH, 'filetypes', 'folder-drag-accept.svg'),
		// TODO: replace primary ?
		color: 'primary',
	},
	'filetype-text': {
		path: join(IMAGE_PATH, 'filetypes', 'text.svg'),
		color: 'grey',
	},
	'file-text': {
		path: join(IMAGE_PATH, 'filetypes', 'text.svg'),
		color: 'black',
	},
}

// use this to define aliases to existing icons
// key is the css selector, value is the variable
const iconsAliases = {
	'icon-caret': 'icon-caret-white',
	// starring action
	'icon-star:hover': 'icon-starred',
	'icon-star:focus': 'icon-starred',
	// Un-starring action
	'icon-starred:hover': 'icon-star-grey',
	'icon-starred:focus': 'icon-star-grey',
	// Delete normal
	'icon-delete.no-permission:hover': 'icon-delete-dark',
	'icon-delete.no-permission:focus': 'icon-delete-dark',
	'icon-delete.no-hover:hover': 'icon-delete-dark',
	'icon-delete.no-hover:focus': 'icon-delete-dark',
	'icon-delete:hover': 'icon-delete-color-red',
	'icon-delete:focus': 'icon-delete-color-red',
	// Delete white
	'icon-delete-white.no-permission:hover': 'icon-delete-white',
	'icon-delete-white.no-permission:focus': 'icon-delete-white',
	'icon-delete-white.no-hover:hover': 'icon-delete-white',
	'icon-delete-white.no-hover:focus': 'icon-delete-white',
	'icon-delete-white:hover': 'icon-delete-color-red',
	'icon-delete-white:focus': 'icon-delete-color-red',
	// Default to white
	'icon-view-close': 'icon-view-close-white',
	'icon-view-download': 'icon-view-download-white',
	'icon-view-pause': 'icon-view-pause-white',
	'icon-view-play': 'icon-view-play-white',
	// Default app place to white
	'icon-calendar': 'icon-calendar-white',
	'icon-contacts': 'icon-contacts-white',
	'icon-files': 'icon-files-white',
	// Re-using existing icons
	'icon-category-installed': 'icon-user-dark',
	'icon-category-enabled': 'icon-checkmark-dark',
	'icon-category-disabled': 'icon-close-dark',
	'icon-category-updates': 'icon-download-dark',
	'icon-category-security': 'icon-password-dark',
	'icon-category-search': 'icon-search-dark',
	'icon-category-tools': 'icon-settings-dark',
	'nav-icon-systemtagsfilter': 'icon-tag-dark',
}

/**
 *
 * @param svg
 * @param color
 */
function colorSvg(svg = '', color = '000') {
	if (!color.match(/^[0-9a-f]{3,6}$/i)) {
		// Prevent not-sane colors from being written into the SVG
		console.warn(color, 'does not match the required format')
		color = '000'
	}

	// add fill (fill is not present on black elements)
	const fillRe = /<((circle|rect|path)((?!fill=)[a-z0-9 =".\-#():;,])+)\/>/gmi
	svg = svg.replace(fillRe, '<$1 fill="#' + color + '"/>')

	// replace any fill or stroke colors
	svg = svg.replace(/stroke="#([a-z0-9]{3,6})"/gmi, 'stroke="#' + color + '"')
	svg = svg.replace(/fill="#([a-z0-9]{3,6})"/gmi, 'fill="#' + color + '"')

	return svg
}

/**
 *
 * @param invert
 */
function generateVariablesAliases(invert = false) {
	let css = ''
	Object.keys(variables).forEach((variable) => {
		if (variable.indexOf('original-') !== -1) {
			let finalVariable = variable.replace('original-', '')
			if (invert) {
				finalVariable = finalVariable.replace('white', 'tempwhite')
					.replace('dark', 'white')
					.replace('tempwhite', 'dark')
			}
			css += `${finalVariable}: var(${variable});`
		}
	})
	return css
}

/**
 *
 * @param icon
 * @param invert
 */
function formatIcon(icon, invert = false) {
	const color1 = invert ? 'white' : 'dark'
	const color2 = invert ? 'dark' : 'white'
	return `
	.icon-${icon},
	.icon-${icon}-dark {
		background-image: var(--icon-${icon}-${color1});
	}
	.icon-${icon}-white,
	.icon-${icon}.icon-white {
		background-image: var(--icon-${icon}-${color2});
	}`
}
/**
 *
 * @param icon
 */
function formatIconColor(icon) {
	const { color } = iconsColor[icon]
	return `
	.icon-${icon} {
		background-image: var(--icon-${icon}-${color});
	}`
}
/**
 *
 * @param alias
 * @param invert
 */
function formatAlias(alias, invert = false) {
	let icon = iconsAliases[alias]
	if (invert) {
		icon = icon.replace('white', 'tempwhite')
			.replace('dark', 'white')
			.replace('tempwhite', 'dark')
	}
	return `
	.${alias} {
		background-image: var(--${icon})
	}`
}

let css = ''
Object.keys(icons).forEach((icon) => {
	const path = icons[icon]

	const svg = readFileSync(path, 'utf8')
	const darkSvg = colorSvg(svg, '000000')
	const whiteSvg = colorSvg(svg, 'ffffff')

	variables[`--original-icon-${icon}-dark`] = Buffer.from(darkSvg, 'utf-8').toString('base64')
	variables[`--original-icon-${icon}-white`] = Buffer.from(whiteSvg, 'utf-8').toString('base64')
})

Object.keys(iconsColor).forEach((icon) => {
	const { path, color } = iconsColor[icon]

	const svg = readFileSync(path, 'utf8')
	const coloredSvg = colorSvg(svg, colors[color])
	variables[`--icon-${icon}-${color}`] = Buffer.from(coloredSvg, 'utf-8').toString('base64')
})

// ICONS VARIABLES LIST
css += ':root {'
Object.keys(variables).forEach((variable) => {
	const data = variables[variable]
	css += `${variable}: url(data:image/svg+xml;base64,${data});`
})
css += '}'

// DEFAULT THEME
css += 'body {'
css += generateVariablesAliases()
Object.keys(icons).forEach((icon) => {
	css += formatIcon(icon)
})
Object.keys(iconsColor).forEach((icon) => {
	css += formatIconColor(icon)
})
Object.keys(iconsAliases).forEach((alias) => {
	css += formatAlias(alias)
})
css += '}'

// DARK THEME MEDIA QUERY
css += '@media (prefers-color-scheme: dark) { body {'
css += generateVariablesAliases(true)
css += '}}'

// DARK THEME
css += '[data-themes*=light] {'
css += generateVariablesAliases()
css += '}'

// DARK THEME
css += '[data-themes*=dark] {'
css += generateVariablesAliases(true)
css += '}'

// WRITE CSS
writeFileSync(join(import.meta.dirname, '../dist', 'icons.css'), compileString(css).css)
