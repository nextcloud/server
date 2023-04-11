/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import type NavigationService from '../services/Navigation.ts'
import type { Navigation } from '../services/Navigation.ts'
import { translate as t } from '@nextcloud/l10n'
import StarSvg from '@mdi/svg/svg/star.svg?raw'
import FolderSvg from '@mdi/svg/svg/folder.svg?raw'

import { getContents } from '../services/Favorites.ts'
import { loadState } from '@nextcloud/initial-state'
import { basename } from 'path'
import { hashCode } from '../utils/hashUtils'
import { subscribe } from '@nextcloud/event-bus'
import { Node, FileType } from '@nextcloud/files'
import logger from '../logger'

const favoriteFolders = loadState('files', 'favoriteFolders', [])

export default () => {
	const Navigation = window.OCP.Files.Navigation as NavigationService
	Navigation.register({
		id: 'favorites',
		name: t('files', 'Favorites'),
		caption: t('files', 'List of favorites files and folders.'),

		icon: StarSvg,
		order: 5,

		columns: [],

		getContents,
	} as Navigation)

	favoriteFolders.forEach((folder) => {
		Navigation.register(generateFolderView(folder))
	})

	/**
	 * Update favourites navigation when a new folder is added
	 */
	subscribe('files:favorites:added', (node: Node) => {
		if (node.type !== FileType.Folder) {
			return
		}

		// Sanity check
		if (node.path === null || !node.root?.startsWith('/files')) {
			logger.error('Favorite folder is not within user files root', { node })
			return
		}

		Navigation.register(generateFolderView(node.path))
	})

	/**
	 * Remove favourites navigation when a folder is removed
	 */
	subscribe('files:favorites:removed', (node: Node) => {
		if (node.type !== FileType.Folder) {
			return
		}

		// Sanity check
		if (node.path === null || !node.root?.startsWith('/files')) {
			logger.error('Favorite folder is not within user files root', { node })
			return
		}

		Navigation.remove(generateIdFromPath(node.path))
	})
}

const generateFolderView = function(folder: string): Navigation {
	return {
		id: generateIdFromPath(folder),
		name: basename(folder),

		icon: FolderSvg,
		order: -100, // always first
		params: {
			dir: folder,
			view: 'favorites',
		},

		parent: 'favorites',

		columns: [],

		getContents,
	} as Navigation
}

const generateIdFromPath = function(path: string): string {
	return `favorite-${hashCode(path)}`
}
