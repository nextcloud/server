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
import type { Navigation, NavigationService } from '../services/Navigation'
import { getLanguage, translate as t } from '@nextcloud/l10n'
import FolderSvg from '@mdi/svg/svg/folder.svg?raw'
import StarSvg from '@mdi/svg/svg/star.svg?raw'

import { basename } from 'path'
import { getContents } from '../services/Favorites'
import { hashCode } from '../utils/hashUtils'
import { loadState } from '@nextcloud/initial-state'
import { Node, FileType } from '@nextcloud/files'
import { subscribe } from '@nextcloud/event-bus'
import logger from '../logger'

export const generateFolderView = function(folder: string, index = 0): Navigation {
	return {
		id: generateIdFromPath(folder),
		name: basename(folder),

		icon: FolderSvg,
		order: index,
		params: {
			dir: folder,
			view: 'favorites',
		},

		parent: 'favorites',

		columns: [],

		getContents,
	} as Navigation
}

export const generateIdFromPath = function(path: string): string {
	return `favorite-${hashCode(path)}`
}

export default () => {
	// Load state in function for mock testing purposes
	const favoriteFolders = loadState<string[]>('files', 'favoriteFolders', [])
	const favoriteFoldersViews = favoriteFolders.map((folder, index) => generateFolderView(folder, index))

	const Navigation = window.OCP.Files.Navigation as NavigationService
	Navigation.register({
		id: 'favorites',
		name: t('files', 'Favorites'),
		caption: t('files', 'List of favorites files and folders.'),

		emptyTitle: t('files', 'No favorites yet'),
		emptyCaption: t('files', 'Files and folders you mark as favorite will show up here'),

		icon: StarSvg,
		order: 5,

		columns: [],

		getContents,
	} as Navigation)

	favoriteFoldersViews.forEach(view => Navigation.register(view))

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

		addPathToFavorites(node.path)
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

		removePathFromFavorites(node.path)
	})

	/**
	 * Sort the favorites paths array and
	 * update the order property of the existing views
	 */
	const updateAndSortViews = function() {
		favoriteFolders.sort((a, b) => a.localeCompare(b, getLanguage(), { ignorePunctuation: true }))
		favoriteFolders.forEach((folder, index) => {
			const view = favoriteFoldersViews.find(view => view.id === generateIdFromPath(folder))
			if (view) {
				view.order = index
			}
		})
	}

	// Add a folder to the favorites paths array and update the views
	const addPathToFavorites = function(path: string) {
		const view = generateFolderView(path)

		// Skip if already exists
		if (favoriteFolders.find(folder => folder === path)) {
			return
		}

		// Update arrays
		favoriteFolders.push(path)
		favoriteFoldersViews.push(view)

		// Update and sort views
		updateAndSortViews()
		Navigation.register(view)
	}

	// Remove a folder from the favorites paths array and update the views
	const removePathFromFavorites = function(path: string) {
		const id = generateIdFromPath(path)
		const index = favoriteFolders.findIndex(folder => folder === path)

		// Skip if not exists
		if (index === -1) {
			return
		}

		// Update arrays
		favoriteFolders.splice(index, 1)
		favoriteFoldersViews.splice(index, 1)

		// Update and sort views
		Navigation.remove(id)
		updateAndSortViews()
	}
}
