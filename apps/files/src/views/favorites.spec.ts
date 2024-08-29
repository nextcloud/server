/* eslint-disable import/no-named-as-default-member */
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Folder as CFolder, Navigation } from '@nextcloud/files'

import { expect } from '@jest/globals'
import * as filesUtils from '@nextcloud/files'
import { CancelablePromise } from 'cancelable-promise'
import * as eventBus from '@nextcloud/event-bus'
import { basename } from 'path'

import { action } from '../actions/favoriteAction'
import * as favoritesService from '../services/Favorites'
import { registerFavoritesView } from './favorites'

const { Folder, getNavigation } = filesUtils

jest.mock('@nextcloud/axios', () => ({
	post: jest.fn(),
}))

jest.mock('webdav/dist/node/request.js', () => ({
	request: jest.fn(),
}))

jest.mock('@nextcloud/files', () => ({
	__esModule: true,
	...jest.requireActual('@nextcloud/files'),
}))

jest.mock('@nextcloud/event-bus', () => ({
	__esModule: true,
	...jest.requireActual('@nextcloud/event-bus'),
}))

window.OC = {
	...window.OC,
	TAG_FAVORITE: '_$!<Favorite>!$_',
}

declare global {
	interface Window {
		_nc_navigation?: Navigation
	}
}

describe('Favorites view definition', () => {
	let Navigation
	beforeEach(() => {
		Navigation = getNavigation()
		expect(window._nc_navigation).toBeDefined()
	})

	afterEach(() => {
		delete window._nc_navigation
	})

	test('Default empty favorite view', async () => {
		jest.spyOn(eventBus, 'subscribe')
		jest.spyOn(filesUtils, 'getFavoriteNodes').mockReturnValue(CancelablePromise.resolve([]))
		jest.spyOn(favoritesService, 'getContents').mockReturnValue(CancelablePromise.resolve({ folder: {} as CFolder, contents: [] }))

		await registerFavoritesView()
		const favoritesView = Navigation.views.find(view => view.id === 'favorites')
		const favoriteFoldersViews = Navigation.views.filter(view => view.parent === 'favorites')

		expect(eventBus.subscribe).toHaveBeenCalledTimes(3)
		expect(eventBus.subscribe).toHaveBeenNthCalledWith(1, 'files:favorites:added', expect.anything())
		expect(eventBus.subscribe).toHaveBeenNthCalledWith(2, 'files:favorites:removed', expect.anything())
		expect(eventBus.subscribe).toHaveBeenNthCalledWith(3, 'files:node:renamed', expect.anything())

		// one main view and no children
		expect(Navigation.views.length).toBe(1)
		expect(favoritesView).toBeDefined()
		expect(favoriteFoldersViews.length).toBe(0)

		expect(favoritesView?.id).toBe('favorites')
		expect(favoritesView?.name).toBe('Favorites')
		expect(favoritesView?.caption).toBeDefined()
		expect(favoritesView?.icon).toBe('<svg>SvgMock</svg>')
		expect(favoritesView?.order).toBe(15)
		expect(favoritesView?.columns).toStrictEqual([])
		expect(favoritesView?.getContents).toBeDefined()
	})

	test('Default with favorites', async () => {
		const favoriteFolders = [
			new Folder({
				id: 1,
				root: '/files/admin',
				source: 'http://nextcloud.local/remote.php/dav/files/admin/foo',
				owner: 'admin',
			}),
			new Folder({
				id: 2,
				root: '/files/admin',
				source: 'http://nextcloud.local/remote.php/dav/files/admin/bar',
				owner: 'admin',
			}),
			new Folder({
				id: 3,
				root: '/files/admin',
				source: 'http://nextcloud.local/remote.php/dav/files/admin/foo/bar',
				owner: 'admin',
			}),
		]
		jest.spyOn(filesUtils, 'getFavoriteNodes').mockReturnValue(CancelablePromise.resolve(favoriteFolders))
		jest.spyOn(favoritesService, 'getContents').mockReturnValue(CancelablePromise.resolve({ folder: {} as CFolder, contents: [] }))

		await registerFavoritesView()
		const favoritesView = Navigation.views.find(view => view.id === 'favorites')
		const favoriteFoldersViews = Navigation.views.filter(view => view.parent === 'favorites')

		// one main view and 3 children
		expect(Navigation.views.length).toBe(4)
		expect(favoritesView).toBeDefined()
		expect(favoriteFoldersViews.length).toBe(3)

		favoriteFolders.forEach((folder, index) => {
			const favoriteView = favoriteFoldersViews[index]
			expect(favoriteView).toBeDefined()
			expect(favoriteView?.id).toBeDefined()
			expect(favoriteView?.name).toBe(basename(folder.path))
			expect(favoriteView?.icon).toBe('<svg>SvgMock</svg>')
			expect(favoriteView?.order).toBe(index)
			expect(favoriteView?.params).toStrictEqual({
				dir: folder.path,
				fileid: String(folder.fileid),
				view: 'favorites',
			})
			expect(favoriteView?.parent).toBe('favorites')
			expect(favoriteView?.columns).toStrictEqual([])
			expect(favoriteView?.getContents).toBeDefined()
		})
	})
})

describe('Dynamic update of favourite folders', () => {
	let Navigation
	beforeEach(() => {
		Navigation = getNavigation()
	})

	afterEach(() => {
		delete window._nc_navigation
	})

	test('Add a favorite folder creates a new entry in the navigation', async () => {
		jest.spyOn(eventBus, 'emit')
		jest.spyOn(filesUtils, 'getFavoriteNodes').mockReturnValue(CancelablePromise.resolve([]))
		jest.spyOn(favoritesService, 'getContents').mockReturnValue(CancelablePromise.resolve({ folder: {} as CFolder, contents: [] }))

		await registerFavoritesView()
		const favoritesView = Navigation.views.find(view => view.id === 'favorites')
		const favoriteFoldersViews = Navigation.views.filter(view => view.parent === 'favorites')

		// one main view and no children
		expect(Navigation.views.length).toBe(1)
		expect(favoritesView).toBeDefined()
		expect(favoriteFoldersViews.length).toBe(0)

		// Create new folder to favorite
		const folder = new Folder({
			id: 1,
			source: 'http://localhost/remote.php/dav/files/admin/Foo/Bar',
			owner: 'admin',
		})

		// Exec the action
		await action.exec(folder, favoritesView, '/')

		expect(eventBus.emit).toHaveBeenCalledTimes(1)
		expect(eventBus.emit).toHaveBeenCalledWith('files:favorites:added', folder)
	})

	test('Remove a favorite folder remove the entry from the navigation column', async () => {
		jest.spyOn(eventBus, 'emit')
		jest.spyOn(eventBus, 'subscribe')
		jest.spyOn(filesUtils, 'getFavoriteNodes').mockReturnValue(CancelablePromise.resolve([
			new Folder({
				id: 42,
				root: '/files/admin',
				source: 'http://nextcloud.local/remote.php/dav/files/admin/Foo/Bar',
				owner: 'admin',
			}),
		]))
		jest.spyOn(favoritesService, 'getContents').mockReturnValue(CancelablePromise.resolve({ folder: {} as CFolder, contents: [] }))

		await registerFavoritesView()
		let favoritesView = Navigation.views.find(view => view.id === 'favorites')
		let favoriteFoldersViews = Navigation.views.filter(view => view.parent === 'favorites')

		// one main view and no children
		expect(Navigation.views.length).toBe(2)
		expect(favoritesView).toBeDefined()
		expect(favoriteFoldersViews.length).toBe(1)

		// Create new folder to favorite
		const folder = new Folder({
			id: 1,
			source: 'http://localhost/remote.php/dav/files/admin/Foo/Bar',
			owner: 'admin',
			root: '/files/admin',
			attributes: {
				favorite: 1,
			},
		})

		// Exec the action
		await action.exec(folder, favoritesView, '/')

		expect(eventBus.emit).toHaveBeenCalledTimes(1)
		expect(eventBus.emit).toHaveBeenCalledWith('files:favorites:removed', folder)

		favoritesView = Navigation.views.find(view => view.id === 'favorites')
		favoriteFoldersViews = Navigation.views.filter(view => view.parent === 'favorites')

		// one main view and no children
		expect(Navigation.views.length).toBe(1)
		expect(favoritesView).toBeDefined()
		expect(favoriteFoldersViews.length).toBe(0)
	})

	test('Renaming a favorite folder updates the navigation', async () => {
		jest.spyOn(eventBus, 'emit')
		jest.spyOn(filesUtils, 'getFavoriteNodes').mockReturnValue(CancelablePromise.resolve([]))
		jest.spyOn(favoritesService, 'getContents').mockReturnValue(CancelablePromise.resolve({ folder: {} as CFolder, contents: [] }))

		await registerFavoritesView()
		const favoritesView = Navigation.views.find(view => view.id === 'favorites')
		const favoriteFoldersViews = Navigation.views.filter(view => view.parent === 'favorites')

		// one main view and no children
		expect(Navigation.views.length).toBe(1)
		expect(favoritesView).toBeDefined()
		expect(favoriteFoldersViews.length).toBe(0)

		// expect(eventBus.emit).toHaveBeenCalledTimes(2)

		// Create new folder to favorite
		const folder = new Folder({
			id: 1,
			source: 'http://localhost/remote.php/dav/files/admin/Foo/Bar',
			owner: 'admin',
		})

		// Exec the action
		await action.exec(folder, favoritesView, '/')
		expect(eventBus.emit).toHaveBeenNthCalledWith(1, 'files:favorites:added', folder)

		// Create a folder with the same id but renamed
		const renamedFolder = new Folder({
			id: 1,
			source: 'http://localhost/remote.php/dav/files/admin/Foo/Bar.renamed',
			owner: 'admin',
		})

		// Exec the rename action
		eventBus.emit('files:node:renamed', renamedFolder)
		expect(eventBus.emit).toHaveBeenNthCalledWith(2, 'files:node:renamed', renamedFolder)
	})
})
