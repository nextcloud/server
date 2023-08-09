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
import { expect } from '@jest/globals'
import * as initialState from '@nextcloud/initial-state'
import { Folder } from '@nextcloud/files'
import { basename } from 'path'
import * as eventBus from '@nextcloud/event-bus'

import { action } from '../actions/favoriteAction'
import * as favoritesService from '../services/Favorites'
import { NavigationService } from '../services/Navigation'
import registerFavoritesView from './favorites'

jest.mock('webdav/dist/node/request.js', () => ({
	request: jest.fn(),
}))

global.window.OC = {
	TAG_FAVORITE: '_$!<Favorite>!$_',
}

describe('Favorites view definition', () => {
	let Navigation
	beforeEach(() => {
		Navigation = new NavigationService()
		window.OCP = { Files: { Navigation } }
	})

	afterAll(() => {
		delete window.OCP
	})

	test('Default empty favorite view', () => {
		jest.spyOn(eventBus, 'subscribe')
		jest.spyOn(favoritesService, 'getContents').mockReturnValue(Promise.resolve({ folder: {} as Folder, contents: [] }))

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
		expect(favoritesView?.order).toBe(5)
		expect(favoritesView?.columns).toStrictEqual([])
		expect(favoritesView?.getContents).toBeDefined()
	})

	test('Default with favorites', () => {
		const favoriteFolders = [
			'/foo',
			'/bar',
			'/foo/bar',
		]
		jest.spyOn(initialState, 'loadState').mockReturnValue(favoriteFolders)
		jest.spyOn(favoritesService, 'getContents').mockReturnValue(Promise.resolve({ folder: {} as Folder, contents: [] }))

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
			expect(favoriteView?.name).toBe(basename(folder))
			expect(favoriteView?.icon).toBe('<svg>SvgMock</svg>')
			expect(favoriteView?.order).toBe(index)
			expect(favoriteView?.params).toStrictEqual({
				dir: folder,
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
		Navigation = new NavigationService()
		window.OCP = { Files: { Navigation } }
	})

	afterAll(() => {
		delete window.OCP
	})

	test('Add a favorite folder creates a new entry in the navigation', async () => {
		jest.spyOn(eventBus, 'emit')
		jest.spyOn(initialState, 'loadState').mockReturnValue([])
		jest.spyOn(favoritesService, 'getContents').mockReturnValue(Promise.resolve({ folder: {} as Folder, contents: [] }))

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
		jest.spyOn(initialState, 'loadState').mockReturnValue(['/Foo/Bar'])
		jest.spyOn(favoritesService, 'getContents').mockReturnValue(Promise.resolve({ folder: {} as Folder, contents: [] }))

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
