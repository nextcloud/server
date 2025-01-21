/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Folder, Node } from '@nextcloud/files'

import { subscribe } from '@nextcloud/event-bus'
import { FileType, View, getFavoriteNodes, getNavigation } from '@nextcloud/files'
import { getLanguage, translate as t } from '@nextcloud/l10n'
import { client } from '../services/WebdavClient.ts'
import FolderSvg from '@mdi/svg/svg/folder.svg?raw'
import StarSvg from '@mdi/svg/svg/star.svg?raw'

import { getContents } from '../services/Favorites'
import { hashCode } from '../utils/hashUtils'
import logger from '../logger'

const generateFavoriteFolderView = function(folder: Folder, index = 0): View {
	return new View({
		id: generateIdFromPath(folder.path),
		name: folder.displayname,

		icon: FolderSvg,
		order: index,

		params: {
			dir: folder.path,
			fileid: String(folder.fileid),
			view: 'favorites',
		},

		parent: 'favorites',

		columns: [],

		getContents,
	})
}

const generateIdFromPath = function(path: string): string {
	return `favorite-${hashCode(path)}`
}

export const registerFavoritesView = async () => {
	const Navigation = getNavigation()
	Navigation.register(new View({
		id: 'favorites',
		name: t('files', 'Favorites'),
		caption: t('files', 'List of favorite files and folders.'),

		emptyTitle: t('files', 'No favorites yet'),
		emptyCaption: t('files', 'Files and folders you mark as favorite will show up here'),

		icon: StarSvg,
		order: 15,

		columns: [],

		getContents,
	}))

	const favoriteFolders = (await getFavoriteNodes(client)).filter(node => node.type === FileType.Folder) as Folder[]
	const favoriteFoldersViews = favoriteFolders.map((folder, index) => generateFavoriteFolderView(folder, index)) as View[]
	logger.debug('Generating favorites view', { favoriteFolders })
	favoriteFoldersViews.forEach(view => Navigation.register(view))

	/**
	 * Update favorites navigation when a new folder is added
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

		addToFavorites(node as Folder)
	})

	/**
	 * Remove favorites navigation when a folder is removed
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
	 * Update favorites navigation when a folder is renamed
	 */
	subscribe('files:node:renamed', (node: Node) => {
		if (node.type !== FileType.Folder) {
			return
		}

		if (node.attributes.favorite !== 1) {
			return
		}

		updateNodeFromFavorites(node as Folder)
	})

	/**
	 * Sort the favorites paths array and
	 * update the order property of the existing views
	 */
	const updateAndSortViews = function() {
		favoriteFolders.sort((a, b) => a.path.localeCompare(b.path, getLanguage(), { ignorePunctuation: true }))
		favoriteFolders.forEach((folder, index) => {
			const view = favoriteFoldersViews.find((view) => view.id === generateIdFromPath(folder.path))
			if (view) {
				view.order = index
			}
		})
	}

	// Add a folder to the favorites paths array and update the views
	const addToFavorites = function(node: Folder) {
		const view = generateFavoriteFolderView(node)

		// Skip if already exists
		if (favoriteFolders.find((folder) => folder.path === node.path)) {
			return
		}

		// Update arrays
		favoriteFolders.push(node)
		favoriteFoldersViews.push(view)

		// Update and sort views
		updateAndSortViews()
		Navigation.register(view)
	}

	// Remove a folder from the favorites paths array and update the views
	const removePathFromFavorites = function(path: string) {
		const id = generateIdFromPath(path)
		const index = favoriteFolders.findIndex((folder) => folder.path === path)

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

	// Update a folder from the favorites paths array and update the views
	const updateNodeFromFavorites = function(node: Folder) {
		const favoriteFolder = favoriteFolders.find((folder) => folder.fileid === node.fileid)

		// Skip if it does not exists
		if (favoriteFolder === undefined) {
			return
		}

		removePathFromFavorites(favoriteFolder.path)
		addToFavorites(node)
	}
}
