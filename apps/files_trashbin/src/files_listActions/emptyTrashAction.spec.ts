/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Node } from '@nextcloud/files'

import * as ncDialogs from '@nextcloud/dialogs'
import * as ncEventBus from '@nextcloud/event-bus'
import { Folder } from '@nextcloud/files'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { trashbinView } from '../files_views/trashbinView.ts'
import * as api from '../services/api.ts'
import { emptyTrashAction } from './emptyTrashAction.ts'

const loadState = vi.hoisted(() => vi.fn((app, key, fallback) => {
	if (fallback !== undefined) {
		return fallback
	}

	console.error('Unexpected loadState call without fallback', { app, key })
	throw new Error()
}))

vi.mock('@nextcloud/initial-state', () => ({
	loadState,
}))

beforeEach(() => vi.resetAllMocks())

describe('files_trashbin: file list actions - empty trashbin', () => {
	it('has id set', () => {
		expect(emptyTrashAction.id).toBe('empty-trash')
	})

	it('has display name set', () => {
		expect(emptyTrashAction.displayName({ view: trashbinView })).toBe('Empty deleted files')
	})

	it('has order set', () => {
		// expect highest priority!
		expect(emptyTrashAction.order).toBe(0)
	})

	it('is enabled on trashbin view', () => {
		loadState.mockImplementation(() => ({ allow_delete: true }))

		const folder = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/', root: '/trashbin/test/' })
		const contents = [
			new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/' }),
		]

		expect(emptyTrashAction.enabled).toBeTypeOf('function')
		expect(emptyTrashAction.enabled!({
			view: trashbinView,
			folder,
			contents,
		})).toBe(true)
		expect(loadState).toHaveBeenCalled()
		expect(loadState).toHaveBeenCalledWith('files_trashbin', 'config')
	})

	it('is not enabled on another view enabled', () => {
		loadState.mockImplementation(() => ({ allow_delete: true }))

		const folder = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/', root: '/trashbin/test/' })
		const contents = [
			new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/' }),
		] as Node[]

		const otherView = new Proxy(trashbinView, {
			get(target, p) {
				if (p === 'id') {
					return 'other-view'
				}
				return target[p]
			},
		})

		expect(emptyTrashAction.enabled).toBeTypeOf('function')
		expect(emptyTrashAction.enabled!({
			view: otherView,
			folder,
			contents,
		})).toBe(false)
	})

	it('is not enabled when deletion is forbidden', () => {
		loadState.mockImplementation(() => ({ allow_delete: false }))

		const folder = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/', root: '/trashbin/test/' })
		const contents = [
			new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/' }),
		]

		expect(emptyTrashAction.enabled).toBeTypeOf('function')
		expect(emptyTrashAction.enabled!({
			view: trashbinView,
			folder,
			contents,
		})).toBe(false)
		expect(loadState).toHaveBeenCalled()
		expect(loadState).toHaveBeenCalledWith('files_trashbin', 'config')
	})

	it('is not enabled when not in trashbin root', () => {
		loadState.mockImplementation(() => ({ allow_delete: true }))
		const folder = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/other-folder', root: '/trashbin/test/' })
		const contents = [
			new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/' }),
		]

		expect(emptyTrashAction.enabled).toBeTypeOf('function')
		expect(emptyTrashAction.enabled!({
			view: trashbinView,
			folder,
			contents,
		})).toBe(false)
	})

	describe('execute', () => {
		const folder = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/', root: '/trashbin/test/' })
		const contents = [
			new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/' }),
		]

		let dialogBuilder = {
			setSeverity: vi.fn(),
			setText: vi.fn(),
			setButtons: vi.fn(),
			build: vi.fn(),
		}

		beforeEach(() => {
			vi.resetAllMocks()
			dialogBuilder = {
				setSeverity: vi.fn(() => dialogBuilder),
				setText: vi.fn(() => dialogBuilder),
				setButtons: vi.fn(() => dialogBuilder),
				build: vi.fn(() => dialogBuilder),
			}

			vi.spyOn(ncDialogs, 'getDialogBuilder')
				// @ts-expect-error This is a mock
				.mockImplementationOnce(() => dialogBuilder)
		})

		it('can cancel the deletion by closing the dialog', async () => {
			const apiSpy = vi.spyOn(api, 'emptyTrash')

			dialogBuilder.build.mockImplementationOnce(() => ({ show: async () => false }))
			expect(await emptyTrashAction.exec({
				view: trashbinView,
				folder,
				contents,
			})).toBe(null)
			expect(apiSpy).not.toBeCalled()
		})

		it('can cancel the deletion', async () => {
			const apiSpy = vi.spyOn(api, 'emptyTrash')

			dialogBuilder.build.mockImplementationOnce(() => ({
				show: async () => {
					const buttons = dialogBuilder.setButtons.mock.calls[0]![0]
					const cancel = buttons.find(({ label }) => label === 'Cancel')
					await cancel.callback()
				},
			}))
			expect(await emptyTrashAction.exec({
				view: trashbinView,
				folder,
				contents,
			})).toBe(null)
			expect(apiSpy).not.toBeCalled()
		})

		it('will trigger the API request if confirmed', async () => {
			const apiSpy = vi.spyOn(api, 'emptyTrash').mockImplementationOnce(async () => true)
			const dialogSpy = vi.spyOn(ncDialogs, 'showInfo')
			const eventBusSpy = vi.spyOn(ncEventBus, 'emit')

			dialogBuilder.build.mockImplementationOnce(() => ({
				show: async () => {
					const buttons = dialogBuilder.setButtons.mock.calls[0]![0]
					const cancel = buttons.find(({ label }) => label === 'Empty deleted files')
					await cancel.callback()
				},
			}))
			expect(await emptyTrashAction.exec({
				view: trashbinView,
				folder,
				contents,
			})).toBe(null)
			expect(apiSpy).toBeCalled()
			expect(dialogSpy).not.toBeCalled()
			expect(eventBusSpy).toBeCalledWith('files:node:deleted', contents[0])
		})

		it('will not emit files deleted event if API request failed', async () => {
			const apiSpy = vi.spyOn(api, 'emptyTrash').mockImplementationOnce(async () => false)
			const dialogSpy = vi.spyOn(ncDialogs, 'showInfo')
			const eventBusSpy = vi.spyOn(ncEventBus, 'emit')

			dialogBuilder.build.mockImplementationOnce(() => ({
				show: async () => {
					const buttons = dialogBuilder.setButtons.mock.calls[0]![0]
					const cancel = buttons.find(({ label }) => label === 'Empty deleted files')
					await cancel.callback()
				},
			}))
			expect(await emptyTrashAction.exec({
				view: trashbinView,
				folder,
				contents,
			})).toBe(null)
			expect(apiSpy).toBeCalled()
			expect(dialogSpy).not.toBeCalled()
			expect(eventBusSpy).not.toBeCalled()
		})
	})
})
