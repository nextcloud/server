/* eslint-disable import/no-named-as-default-member */
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { basename } from 'path'
import { expect } from '@jest/globals'
import { Folder, Navigation, getNavigation } from '@nextcloud/files'
import { CancelablePromise } from 'cancelable-promise'
import eventBus from '@nextcloud/event-bus'
import * as initialState from '@nextcloud/initial-state'

import { action } from '../actions/favoriteAction'
import * as favoritesService from '../services/Favorites'
import registerFavoritesView from './favorites'

jest.mock('webdav/dist/node/request.js', () => ({
	request: jest.fn(),
}))

global.window.OC = {
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

	test('Default empty favorite view', () => {
		jest.spyOn(eventBus, 'subscribe')
		jest.spyOn(favoritesService, 'getContents').mockReturnValue(CancelablePromise.resolve({ folder: {} as Folder, contents: [] }))

		registerFavoritesView()
		const favoritesView = Navigation.views.find(view => view.id === 'favorites')
		const favoriteFoldersViews = Navigation.views.filter(view => view.parent === 'favorites')

		expect(eventBus.subscribe).toHaveBeenCalledTimes(2)
		expect(eventBus.subscribe).toHaveBeenNthCalledWith(1, 'files:favorites:added', expect.anything())
		expect(eventBus.subscribe).toHaveBeenNthCalledWith(2, 'files:favorites:removed', expect.anything())

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

	test('Default with favorites', () => {
		const favoriteFolders = [
			{ fileid: 1, path: '/foo' },
			{ fileid: 2, path: '/bar' },
			{ fileid: 3, path: '/foo/bar' },
		]
		jest.spyOn(initialState, 'loadState').mockReturnValue(favoriteFolders)
		jest.spyOn(favoritesService, 'getContents').mockReturnValue(CancelablePromise.resolve({ folder: {} as Folder, contents: [] }))

		registerFavoritesView()
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
				fileid: folder.fileid.toString(),
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
		jest.spyOn(initialState, 'loadState').mockReturnValue([])
		jest.spyOn(favoritesService, 'getContents').mockReturnValue(CancelablePromise.resolve({ folder: {} as Folder, contents: [] }))

		registerFavoritesView()
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
		jest.spyOn(initialState, 'loadState').mockReturnValue([{ fileid: 42, path: '/Foo/Bar' }])
		jest.spyOn(favoritesService, 'getContents').mockReturnValue(CancelablePromise.resolve({ folder: {} as Folder, contents: [] }))

		registerFavoritesView()
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
})
