/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { File, Permission, View, FileAction } from '@nextcloud/files'
import { beforeAll, beforeEach, describe, expect, test, vi } from 'vitest'

import axios from '@nextcloud/axios'
import * as nextcloudDialogs from '@nextcloud/dialogs'
import { action } from './editLocallyAction'

vi.mock('@nextcloud/auth')
vi.mock('@nextcloud/axios')

const view = {
	id: 'files',
	name: 'Files',
} as View

// Mock web root variable
beforeAll(() => {
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	(window as any)._oc_webroot = '';
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	(window as any).OCA = { Viewer: { open: vi.fn() } }
})

describe('Edit locally action conditions tests', () => {
	test('Default values', () => {
		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('edit-locally')
		expect(action.displayName([], view)).toBe('Edit locally')
		expect(action.iconSvgInline([], view)).toMatch(/<svg.+<\/svg>/)
		expect(action.default).toBeUndefined()
		expect(action.order).toBe(25)
	})
})

describe('Edit locally action enabled tests', () => {
	test('Enabled for file with UPDATE permission', () => {
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

	test('Disabled for non-dav resources', () => {
		const file = new File({
			id: 1,
			source: 'https://domain.com/data/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(false)
	})

	test('Disabled if more than one node', () => {
		const file1 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foo.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
		})
		const file2 = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/bar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file1, file2], view)).toBe(false)
	})

	test('Disabled for files', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/',
			owner: 'admin',
			mime: 'text/plain',
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(false)
	})

	test('Disabled without UPDATE permissions', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.READ,
		})

		expect(action.enabled).toBeDefined()
		expect(action.enabled!([file], view)).toBe(false)
	})
})

describe('Edit locally action execute tests', () => {
	let spyShowDialog
	beforeEach(() => {
		vi.resetAllMocks()
		spyShowDialog = vi.spyOn(nextcloudDialogs.Dialog.prototype, 'show')
			.mockImplementation(() => Promise.resolve())
	})

	test('Edit locally opens proper URL', async () => {
		vi.spyOn(axios, 'post').mockImplementation(async () => ({
			data: { ocs: { data: { token: 'foobar' } } },
		}))
		const showError = vi.spyOn(nextcloudDialogs, 'showError')
		const windowOpenSpy = vi.spyOn(window, 'open').mockImplementation(() => null)

		const file = new File({
			id: 1,
			source: 'http://nextcloud.local/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.UPDATE,
		})

		const exec = await action.exec(file, view, '/')

		expect(spyShowDialog).toBeCalled()

		// Silent action
		expect(exec).toBe(null)
		expect(axios.post).toBeCalledTimes(1)
		expect(axios.post).toBeCalledWith('http://nextcloud.local/ocs/v2.php/apps/files/api/v1/openlocaleditor?format=json', { path: '/foobar.txt' })
		expect(showError).toBeCalledTimes(0)
		expect(windowOpenSpy).toBeCalledWith('nc://open/test@nextcloud.local/foobar.txt?token=foobar', '_self')
	})

	test('Edit locally fails and shows error', async () => {
		vi.spyOn(axios, 'post').mockImplementation(async () => ({}))
		const showError = vi.spyOn(nextcloudDialogs, 'showError')

		const file = new File({
			id: 1,
			source: 'http://nextcloud.local/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.UPDATE,
		})

		const exec = await action.exec(file, view, '/')

		expect(spyShowDialog).toBeCalled()

		// Silent action
		expect(exec).toBe(null)
		expect(axios.post).toBeCalledTimes(1)
		expect(axios.post).toBeCalledWith('http://nextcloud.local/ocs/v2.php/apps/files/api/v1/openlocaleditor?format=json', { path: '/foobar.txt' })
		expect(showError).toBeCalledTimes(1)
		expect(showError).toBeCalledWith('Failed to redirect to client')
		expect(window.location.href).toBe('http://nextcloud.local/')
	})
})
