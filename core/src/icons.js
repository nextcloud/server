/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/* eslint-disable quote-props */
/* eslint-disable n/no-unpublished-import */
import path from 'path'
import fs from 'fs'
import sass from 'sass'

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
	'add': path.join(__dirname, '../img', 'actions', 'add.svg'),
	'address': path.join(__dirname, '../img', 'actions', 'address.svg'),
	'alert-outline': path.join(__dirname, '../img', 'actions', 'alert-outline.svg'),
	'audio-off': path.join(__dirname, '../img', 'actions', 'audio-off.svg'),
	'audio': path.join(__dirname, '../img', 'actions', 'audio.svg'),
	'calendar': path.join(__dirname, '../img', 'places', 'calendar.svg'),
	'caret': path.join(__dirname, '../img', 'actions', 'caret.svg'),
	'category-app-bundles': path.join(__dirname, '../img', 'categories', 'bundles.svg'),
	'category-auth': path.join(__dirname, '../img', 'categories', 'auth.svg'),
	'category-customization': path.join(__dirname, '../img', 'categories', 'customization.svg'),
	'category-dashboard': path.join(__dirname, '../img', 'categories', 'dashboard.svg'),
	'category-files': path.join(__dirname, '../img', 'categories', 'files.svg'),
	'category-games': path.join(__dirname, '../img', 'categories', 'games.svg'),
	'category-integration': path.join(__dirname, '../img', 'categories', 'integration.svg'),
	'category-monitoring': path.join(__dirname, '../img', 'categories', 'monitoring.svg'),
	'category-multimedia': path.join(__dirname, '../img', 'categories', 'multimedia.svg'),
	'category-office': path.join(__dirname, '../img', 'categories', 'office.svg'),
	'category-organization': path.join(__dirname, '../img', 'categories', 'organization.svg'),
	'category-social': path.join(__dirname, '../img', 'categories', 'social.svg'),
	'category-workflow': path.join(__dirname, '../img', 'categories', 'workflow.svg'),
	'change': path.join(__dirname, '../img', 'actions', 'change.svg'),
	'checkmark': path.join(__dirname, '../img', 'actions', 'checkmark.svg'),
	'circles': path.join(__dirname, '../img', 'apps', 'circles.svg'),
	'clippy': path.join(__dirname, '../img', 'actions', 'clippy.svg'),
	'close': path.join(__dirname, '../img', 'actions', 'close.svg'),
	'comment': path.join(__dirname, '../img', 'actions', 'comment.svg'),
	'confirm-fade': path.join(__dirname, '../img', 'actions', 'confirm-fade.svg'),
	'confirm': path.join(__dirname, '../img', 'actions', 'confirm.svg'),
	'contacts': path.join(__dirname, '../img', 'places', 'contacts.svg'),
	'delete': path.join(__dirname, '../img', 'actions', 'delete.svg'),
	'desktop': path.join(__dirname, '../img', 'clients', 'desktop.svg'),
	'details': path.join(__dirname, '../img', 'actions', 'details.svg'),
	'disabled-user': path.join(__dirname, '../img', 'actions', 'disabled-user.svg'),
	'disabled-users': path.join(__dirname, '../img', 'actions', 'disabled-users.svg'),
	'download': path.join(__dirname, '../img', 'actions', 'download.svg'),
	'edit': path.join(__dirname, '../img', 'actions', 'edit.svg'),
	'encryption': path.join(__dirname, '../../', 'apps/files_external/img', 'app.svg'),
	'error': path.join(__dirname, '../img', 'actions', 'error.svg'),
	'external': path.join(__dirname, '../img', 'actions', 'external.svg'),
	'favorite': path.join(__dirname, '../img', 'actions', 'star-dark.svg'),
	'files': path.join(__dirname, '../img', 'places', 'files.svg'),
	'filter': path.join(__dirname, '../img', 'actions', 'filter.svg'),
	'folder': path.join(__dirname, '../img', 'filetypes', 'folder.svg'),
	'fullscreen': path.join(__dirname, '../img', 'actions', 'fullscreen.svg'),
	'group': path.join(__dirname, '../img', 'actions', 'group.svg'),
	'history': path.join(__dirname, '../img', 'actions', 'history.svg'),
	'home': path.join(__dirname, '../img', 'places', 'home.svg'),
	'info': path.join(__dirname, '../img', 'actions', 'info.svg'),
	'link': path.join(__dirname, '../img', 'places', 'link.svg'),
	'logout': path.join(__dirname, '../img', 'actions', 'logout.svg'),
	'mail': path.join(__dirname, '../img', 'actions', 'mail.svg'),
	'menu-sidebar': path.join(__dirname, '../img', 'actions', 'menu-sidebar.svg'),
	'menu': path.join(__dirname, '../img', 'actions', 'menu.svg'),
	'more': path.join(__dirname, '../img', 'actions', 'more.svg'),
	'music': path.join(__dirname, '../img', 'places', 'music.svg'),
	'password': path.join(__dirname, '../img', 'actions', 'password.svg'),
	'pause': path.join(__dirname, '../img', 'actions', 'pause.svg'),
	'phone': path.join(__dirname, '../img', 'clients', 'phone.svg'),
	'picture': path.join(__dirname, '../img', 'places', 'picture.svg'),
	'play-add': path.join(__dirname, '../img', 'actions', 'play-add.svg'),
	'play-next': path.join(__dirname, '../img', 'actions', 'play-next.svg'),
	'play-previous': path.join(__dirname, '../img', 'actions', 'play-previous.svg'),
	'play': path.join(__dirname, '../img', 'actions', 'play.svg'),
	'projects': path.join(__dirname, '../img', 'actions', 'projects.svg'),
	'public': path.join(__dirname, '../img', 'actions', 'public.svg'),
	'quota': path.join(__dirname, '../img', 'actions', 'quota.svg'),
	'recent': path.join(__dirname, '../img', 'actions', 'recent.svg'),
	'rename': path.join(__dirname, '../img', 'actions', 'rename.svg'),
	'screen-off': path.join(__dirname, '../img', 'actions', 'screen-off.svg'),
	'screen': path.join(__dirname, '../img', 'actions', 'screen.svg'),
	'search': path.join(__dirname, '../img', 'actions', 'search.svg'),
	'settings': path.join(__dirname, '../img', 'actions', 'settings-dark.svg'),
	'share': path.join(__dirname, '../img', 'actions', 'share.svg'),
	'shared': path.join(__dirname, '../img', 'actions', 'share.svg'),
	'sound-off': path.join(__dirname, '../img', 'actions', 'sound-off.svg'),
	'sound': path.join(__dirname, '../img', 'actions', 'sound.svg'),
	'star': path.join(__dirname, '../img', 'actions', 'star.svg'),
	'starred': path.join(__dirname, '../img', 'actions', 'star-dark.svg'),
	'star-rounded': path.join(__dirname, '../img', 'actions', 'star-rounded.svg'),
	'tablet': path.join(__dirname, '../img', 'clients', 'tablet.svg'),
	'tag': path.join(__dirname, '../img', 'actions', 'tag.svg'),
	'talk': path.join(__dirname, '../img', 'apps', 'spreed.svg'),
	'teams': path.join(__dirname, '../img', 'apps', 'circles.svg'),
	'template-add': path.join(__dirname, '../img', 'actions', 'template-add.svg'),
	'timezone': path.join(__dirname, '../img', 'actions', 'timezone.svg'),
	'toggle-background': path.join(__dirname, '../img', 'actions', 'toggle-background.svg'),
	'toggle-filelist': path.join(__dirname, '../img', 'actions', 'toggle-filelist.svg'),
	'toggle-pictures': path.join(__dirname, '../img', 'actions', 'toggle-pictures.svg'),
	'toggle': path.join(__dirname, '../img', 'actions', 'toggle.svg'),
	'triangle-e': path.join(__dirname, '../img', 'actions', 'triangle-e.svg'),
	'triangle-n': path.join(__dirname, '../img', 'actions', 'triangle-n.svg'),
	'triangle-s': path.join(__dirname, '../img', 'actions', 'triangle-s.svg'),
	'unshare': path.join(__dirname, '../img', 'actions', 'unshare.svg'),
	'upload': path.join(__dirname, '../img', 'actions', 'upload.svg'),
	'user-admin': path.join(__dirname, '../img', 'actions', 'user-admin.svg'),
	'user': path.join(__dirname, '../img', 'actions', 'user.svg'),
	'video-off': path.join(__dirname, '../img', 'actions', 'video-off.svg'),
	'video-switch': path.join(__dirname, '../img', 'actions', 'video-switch.svg'),
	'video': path.join(__dirname, '../img', 'actions', 'video.svg'),
	'view-close': path.join(__dirname, '../img', 'actions', 'view-close.svg'),
	'view-download': path.join(__dirname, '../img', 'actions', 'view-download.svg'),
	'view-next': path.join(__dirname, '../img', 'actions', 'arrow-right.svg'),
	'view-pause': path.join(__dirname, '../img', 'actions', 'view-pause.svg'),
	'view-play': path.join(__dirname, '../img', 'actions', 'view-play.svg'),
	'view-previous': path.join(__dirname, '../img', 'actions', 'arrow-left.svg'),
}

const iconsColor = {
	'add-folder-description': {
		path: path.join(__dirname, '../img', 'actions', 'add-folder-description.svg'),
		color: 'grey',
	},
	'settings': {
		path: path.join(__dirname, '../img', 'actions', 'settings.svg'),
		color: 'black',
	},
	'error-color': {
		path: path.join(__dirname, '../img', 'actions', 'error.svg'),
		color: 'red',
	},
	'checkmark-color': {
		path: path.join(__dirname, '../img', 'actions', 'checkmark.svg'),
		color: 'green',
	},
	'starred': {
		path: path.join(__dirname, '../img', 'actions', 'star-dark.svg'),
		color: 'yellow',
	},
	'star': {
		path: path.join(__dirname, '../img', 'actions', 'star-dark.svg'),
		color: 'grey',
	},
	'delete-color': {
		path: path.join(__dirname, '../img', 'actions', 'delete.svg'),
		color: 'red',
	},
	'file': {
		path: path.join(__dirname, '../img', 'filetypes', 'text.svg'),
		color: 'grey',
	},
	'filetype-file': {
		path: path.join(__dirname, '../img', 'filetypes', 'file.svg'),
		color: 'grey',
	},
	'filetype-folder': {
		path: path.join(__dirname, '../img', 'filetypes', 'folder.svg'),
		// TODO: replace primary ?
		color: 'primary',
	},
	'filetype-folder-drag-accept': {
		path: path.join(__dirname, '../img', 'filetypes', 'folder-drag-accept.svg'),
		// TODO: replace primary ?
		color: 'primary',
	},
	'filetype-text': {
		path: path.join(__dirname, '../img', 'filetypes', 'text.svg'),
		color: 'grey',
	},
	'file-text': {
		path: path.join(__dirname, '../img', 'filetypes', 'text.svg'),
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

const colorSvg = function(svg = '', color = '000') {
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

const generateVariablesAliases = function(invert = false) {
	let css = ''
	Object.keys(variables).forEach(variable => {
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

const formatIcon = function(icon, invert = false) {
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
const formatIconColor = function(icon) {
	const { color } = iconsColor[icon]
	return `
	.icon-${icon} {
		background-image: var(--icon-${icon}-${color});
	}`
}
const formatAlias = function(alias, invert = false) {
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
Object.keys(icons).forEach(icon => {
	const path = icons[icon]

	const svg = fs.readFileSync(path, 'utf8')
	const darkSvg = colorSvg(svg, '000000')
	const whiteSvg = colorSvg(svg, 'ffffff')

	variables[`--original-icon-${icon}-dark`] = Buffer.from(darkSvg, 'utf-8').toString('base64')
	variables[`--original-icon-${icon}-white`] = Buffer.from(whiteSvg, 'utf-8').toString('base64')
})

Object.keys(iconsColor).forEach(icon => {
	const { path, color } = iconsColor[icon]

	const svg = fs.readFileSync(path, 'utf8')
	const coloredSvg = colorSvg(svg, colors[color])
	variables[`--icon-${icon}-${color}`] = Buffer.from(coloredSvg, 'utf-8').toString('base64')
})

// ICONS VARIABLES LIST
css += ':root {'
Object.keys(variables).forEach(variable => {
	const data = variables[variable]
	css += `${variable}: url(data:image/svg+xml;base64,${data});`
})
css += '}'

// DEFAULT THEME
css += 'body {'
css += generateVariablesAliases()
Object.keys(icons).forEach(icon => {
	css += formatIcon(icon)
})
Object.keys(iconsColor).forEach(icon => {
	css += formatIconColor(icon)
})
Object.keys(iconsAliases).forEach(alias => {
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
fs.writeFileSync(path.join(__dirname, '../../dist', 'icons.css'), sass.compileString(css).css)
