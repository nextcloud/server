/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { action } from './editLocallyAction'
import { expect } from '@jest/globals'
import { File, Permission, View, FileAction } from '@nextcloud/files'
import { DialogBuilder, showError } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'

const dialogBuilder = {
	setName: jest.fn().mockReturnThis(),
	setText: jest.fn().mockReturnThis(),
	setButtons: jest.fn().mockReturnThis(),
	build: jest.fn().mockReturnValue({
		show: jest.fn().mockResolvedValue(true),
	}),
} as unknown as DialogBuilder

jest.mock('@nextcloud/dialogs', () => ({
	DialogBuilder: jest.fn(() => dialogBuilder),
	showError: jest.fn(),
}))

const view = {
	id: 'files',
	name: 'Files',
} as View

// Mock webroot variable
beforeAll(() => {
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	(window as any)._oc_webroot = '';
	(window as any).OCA = { Viewer: { open: jest.fn() } }
})

describe('Edit locally action conditions tests', () => {
	test('Default values', () => {
		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('edit-locally')
		expect(action.displayName([], view)).toBe('Edit locally')
		expect(action.iconSvgInline([], view)).toBe('<svg>SvgMock</svg>')
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
	test('Edit locally opens proper URL', async () => {
		jest.spyOn(axios, 'post').mockImplementation(async () => ({
			data: { ocs: { data: { token: 'foobar' } } },
		}))
		const mockedShowError = jest.mocked(showError)
		const spyDialogBuilder = jest.spyOn(dialogBuilder, 'build')

		const file = new File({
			id: 1,
			source: 'http://localhost/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.UPDATE,
		})

		const exec = await action.exec(file, view, '/')

		expect(spyDialogBuilder).toBeCalled()

		// Silent action
		expect(exec).toBe(null)
		expect(axios.post).toBeCalledTimes(1)
		expect(axios.post).toBeCalledWith('http://localhost/ocs/v2.php/apps/files/api/v1/openlocaleditor?format=json', { path: '/foobar.txt' })
		expect(mockedShowError).toBeCalledTimes(0)
		expect(window.location.href).toBe('nc://open/test@localhost/foobar.txt?token=foobar')
	})

	test('Edit locally fails and shows error', async () => {
		jest.spyOn(axios, 'post').mockImplementation(async () => ({}))
		const mockedShowError = jest.mocked(showError)
		const spyDialogBuilder = jest.spyOn(dialogBuilder, 'build')

		const file = new File({
			id: 1,
			source: 'http://localhost/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.UPDATE,
		})

		const exec = await action.exec(file, view, '/')

		expect(spyDialogBuilder).toBeCalled()

		// Silent action
		expect(exec).toBe(null)
		expect(axios.post).toBeCalledTimes(1)
		expect(axios.post).toBeCalledWith('http://localhost/ocs/v2.php/apps/files/api/v1/openlocaleditor?format=json', { path: '/foobar.txt' })
		expect(mockedShowError).toBeCalledTimes(1)
		expect(mockedShowError).toBeCalledWith('Failed to redirect to client')
		expect(window.location.href).toBe('http://localhost/')
	})
})
