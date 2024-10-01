/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { File, Permission, View, FileAction } from '@nextcloud/files'
import { beforeAll, beforeEach, describe, expect, test, vi } from 'vitest'

import { action } from './favoriteAction'
import axios from '@nextcloud/axios'
import * as eventBus from '@nextcloud/event-bus'
import * as favoriteAction from './favoriteAction'
import logger from '../logger'

vi.mock('@nextcloud/auth')
vi.mock('@nextcloud/axios')

const view = {
	id: 'files',
	name: 'Files',
} as View

const favoriteView = {
	id: 'favorites',
	name: 'Favorites',
} as View

// Mock webroot variable
beforeAll(() => {
	window.OC = {
		...window.OC,
		TAG_FAVORITE: '_$!<Favorite>!$_',
	};
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	(window as any)._oc_webroot = ''
})

describe('Favorite action conditions tests', () => {
	test('Default values', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
		})

		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('favorite')
		expect(action.displayName([file], view)).toBe('Add to favorites')
		expect(action.iconSvgInline([], view)).toMatch(/<svg.+<\/svg>/)
		expect(action.default).toBeUndefined()
		expect(action.order).toBe(-50)
	})

	test('Display name is Remove from favorites if already in favorites', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			attributes: {
				favorite: 1,
			},
		})

		expect(action.displayName([file], view)).toBe('Remove from favorites')
	})

	test('Display name for multiple state files', () => {
		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			attributes: {
				favorite: 1,
			},
		})
		const file2 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			attributes: {
				favorite: 0,
			},
		})
		const file3 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			attributes: {
				favorite: 1,
			},
		})

		expect(action.displayName([file1, file2, file3], view)).toBe('Add to favorites')
		expect(action.displayName([file1, file2], view)).toBe('Add to favorites')
		expect(action.displayName([file2, file3], view)).toBe('Add to favorites')
		expect(action.displayName([file1, file3], view)).toBe('Remove from favorites')
	})
})

describe('Favorite action enabled tests', () => {
	test('Enabled for dav file', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(true)
	})

	test('Disabled for non-dav ressources', () => {
		const file = new File({
			id: 1,
			source: 'https://domain.com/data/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(false)
	})
})

describe('Favorite action execute tests', () => {
	beforeEach(() => { vi.resetAllMocks() })

	test('Favorite triggers tag addition', async () => {
		vi.spyOn(axios, 'post')
		vi.spyOn(eventBus, 'emit')

		const file = new File({
			id: 1,
			source: 'http://localhost/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
		})

		const exec = await action.exec(file, view, '/')

		expect(exec).toBe(true)

		// Check POST request
		expect(axios.post).toBeCalledTimes(1)
		expect(axios.post).toBeCalledWith('/index.php/apps/files/api/v1/files/foobar.txt', { tags: ['_$!<Favorite>!$_'] })

		// Check node change propagation
		expect(file.attributes.favorite).toBe(1)
		expect(eventBus.emit).toBeCalledTimes(1)
		expect(eventBus.emit).toBeCalledWith('files:favorites:added', file)
	})

	test('Favorite triggers tag removal', async () => {
		vi.spyOn(axios, 'post')
		vi.spyOn(eventBus, 'emit')

		const file = new File({
			id: 1,
			source: 'http://localhost/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			attributes: {
				favorite: 1,
			},
		})

		const exec = await action.exec(file, view, '/')

		expect(exec).toBe(true)

		// Check POST request
		expect(axios.post).toBeCalledTimes(1)
		expect(axios.post).toBeCalledWith('/index.php/apps/files/api/v1/files/foobar.txt', { tags: [] })

		// Check node change propagation
		expect(file.attributes.favorite).toBe(0)
		expect(eventBus.emit).toBeCalledTimes(1)
		expect(eventBus.emit).toBeCalledWith('files:favorites:removed', file)
	})

	test('Favorite triggers node removal if favorite view and root dir', async () => {
		vi.spyOn(axios, 'post')
		vi.spyOn(eventBus, 'emit')

		const file = new File({
			id: 1,
			source: 'http://localhost/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			attributes: {
				favorite: 1,
			},
		})

		const exec = await action.exec(file, favoriteView, '/')

		expect(exec).toBe(true)

		// Check POST request
		expect(axios.post).toBeCalledTimes(1)
		expect(axios.post).toBeCalledWith('/index.php/apps/files/api/v1/files/foobar.txt', { tags: [] })

		// Check node change propagation
		expect(file.attributes.favorite).toBe(0)
		expect(eventBus.emit).toBeCalledTimes(2)
		expect(eventBus.emit).toHaveBeenNthCalledWith(1, 'files:node:deleted', file)
		expect(eventBus.emit).toHaveBeenNthCalledWith(2, 'files:favorites:removed', file)
	})

	test('Favorite does NOT triggers node removal if favorite view but NOT root dir', async () => {
		vi.spyOn(axios, 'post')
		vi.spyOn(eventBus, 'emit')

		const file = new File({
			id: 1,
			source: 'http://localhost/remote.php/dav/files/admin/Foo/Bar/foobar.txt',
			root: '/files/admin',
			owner: 'admin',
			mime: 'text/plain',
			attributes: {
				favorite: 1,
			},
		})

		const exec = await action.exec(file, favoriteView, '/')

		expect(exec).toBe(true)

		// Check POST request
		expect(axios.post).toBeCalledTimes(1)
		expect(axios.post).toBeCalledWith('/index.php/apps/files/api/v1/files/Foo/Bar/foobar.txt', { tags: [] })

		// Check node change propagation
		expect(file.attributes.favorite).toBe(0)
		expect(eventBus.emit).toBeCalledTimes(1)
		expect(eventBus.emit).toBeCalledWith('files:favorites:removed', file)
	})

	test('Favorite fails and show error', async () => {
		const error = new Error('Mock error')
		vi.spyOn(axios, 'post').mockImplementation(() => { throw new Error('Mock error') })
		vi.spyOn(logger, 'error').mockImplementation(() => vi.fn())

		const file = new File({
			id: 1,
			source: 'http://localhost/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			attributes: {
				favorite: 0,
			},
		})

		const exec = await action.exec(file, view, '/')

		expect(exec).toBe(false)

		// Check POST request
		expect(axios.post).toBeCalledTimes(1)
		expect(axios.post).toBeCalledWith('/index.php/apps/files/api/v1/files/foobar.txt', { tags: ['_$!<Favorite>!$_'] })

		// Check node change propagation
		expect(logger.error).toBeCalledTimes(1)
		expect(logger.error).toBeCalledWith('Error while adding a file to favourites', { error, source: file.source, node: file })
		expect(file.attributes.favorite).toBe(0)
		expect(eventBus.emit).toBeCalledTimes(0)
	})

	test('Removing from favorites fails and show error', async () => {
		const error = new Error('Mock error')
		vi.spyOn(axios, 'post').mockImplementation(() => { throw error })
		vi.spyOn(logger, 'error').mockImplementation(() => vi.fn())

		const file = new File({
			id: 1,
			source: 'http://localhost/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			attributes: {
				favorite: 1,
			},
		})

		const exec = await action.exec(file, view, '/')

		expect(exec).toBe(false)

		// Check POST request
		expect(axios.post).toBeCalledTimes(1)
		expect(axios.post).toBeCalledWith('/index.php/apps/files/api/v1/files/foobar.txt', { tags: [] })

		// Check node change propagation
		expect(logger.error).toBeCalledTimes(1)
		expect(logger.error).toBeCalledWith('Error while removing a file from favourites', { error, source: file.source, node: file })
		expect(file.attributes.favorite).toBe(1)
		expect(eventBus.emit).toBeCalledTimes(0)
	})
})

describe('Favorite action batch execute tests', () => {
	beforeEach(() => { vi.restoreAllMocks() })

	test('Favorite action batch execute with mixed files', async () => {
		vi.spyOn(favoriteAction, 'favoriteNode')
		vi.spyOn(axios, 'post')

		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foo.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			attributes: {
				favorite: 1,
			},
		})
		const file2 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/bar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			attributes: {
				favorite: 0,
			},
		})

		// Mixed states triggers favorite action
		const exec = await action.execBatch!([file1, file2], view, '/')
		expect(exec).toStrictEqual([true, true])
		expect([file1, file2].every(file => file.attributes.favorite === 1)).toBe(true)

		expect(axios.post).toBeCalledTimes(2)
		expect(axios.post).toHaveBeenNthCalledWith(1, '/index.php/apps/files/api/v1/files/foo.txt', { tags: ['_$!<Favorite>!$_'] })
		expect(axios.post).toHaveBeenNthCalledWith(2, '/index.php/apps/files/api/v1/files/bar.txt', { tags: ['_$!<Favorite>!$_'] })
	})

	test('Remove from favorite action batch execute with favorites only files', async () => {
		vi.spyOn(favoriteAction, 'favoriteNode')
		vi.spyOn(axios, 'post')

		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foo.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			attributes: {
				favorite: 1,
			},
		})
		const file2 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/bar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			attributes: {
				favorite: 1,
			},
		})

		// Mixed states triggers favorite action
		const exec = await action.execBatch!([file1, file2], view, '/')
		expect(exec).toStrictEqual([true, true])
		expect([file1, file2].every(file => file.attributes.favorite === 0)).toBe(true)

		expect(axios.post).toBeCalledTimes(2)
		expect(axios.post).toHaveBeenNthCalledWith(1, '/index.php/apps/files/api/v1/files/foo.txt', { tags: [] })
		expect(axios.post).toHaveBeenNthCalledWith(2, '/index.php/apps/files/api/v1/files/bar.txt', { tags: [] })
	})
})
