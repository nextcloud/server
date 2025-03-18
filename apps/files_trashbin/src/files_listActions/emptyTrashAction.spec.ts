/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Folder } from '@nextcloud/files'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { emptyTrashAction } from './emptyTrashAction.ts'
import { trashbinView } from '../files_views/trashbinView.ts'
import * as ncDialogs from '@nextcloud/dialogs'
import * as ncEventBus from '@nextcloud/event-bus'
import * as ncInitialState from '@nextcloud/initial-state'
import * as api from '../services/api.ts'

describe('files_trashbin: file list actions - empty trashbin', () => {
	it('has id set', () => {
		expect(emptyTrashAction.id).toBe('empty-trash')
	})

	it('has display name set', () => {
		expect(emptyTrashAction.displayName(trashbinView)).toBe('Empty deleted files')
	})

	it('has order set', () => {
		// expect highest priority!
		expect(emptyTrashAction.order).toBe(0)
	})

	it('is enabled on trashbin view', () => {
		const spy = vi.spyOn(ncInitialState, 'loadState').mockImplementationOnce(() => ({ allow_delete: true }))

		const root = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/', root: '/trashbin/test/' })
		const nodes = [
			new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/' }),
		]

		expect(emptyTrashAction.enabled).toBeTypeOf('function')
		expect(emptyTrashAction.enabled!(trashbinView, nodes, root)).toBe(true)
		expect(spy).toHaveBeenCalled()
		expect(spy).toHaveBeenCalledWith('files_trashbin', 'config')
	})

	it('is not enabled on another view enabled', () => {
		vi.spyOn(ncInitialState, 'loadState').mockImplementationOnce(() => ({ allow_delete: true }))

		const root = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/', root: '/trashbin/test/' })
		const nodes = [
			new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/' }),
		]

		const otherView = new Proxy(trashbinView, {
			get(target, p) {
				if (p === 'id') {
					return 'other-view'
				}
				return target[p]
			},
		})

		expect(emptyTrashAction.enabled).toBeTypeOf('function')
		expect(emptyTrashAction.enabled!(otherView, nodes, root)).toBe(false)
	})

	it('is not enabled when deletion is forbidden', () => {
		const spy = vi.spyOn(ncInitialState, 'loadState').mockImplementationOnce(() => ({ allow_delete: false }))

		const root = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/', root: '/trashbin/test/' })
		const nodes = [
			new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/' }),
		]

		expect(emptyTrashAction.enabled).toBeTypeOf('function')
		expect(emptyTrashAction.enabled!(trashbinView, nodes, root)).toBe(false)
		expect(spy).toHaveBeenCalled()
		expect(spy).toHaveBeenCalledWith('files_trashbin', 'config')
	})

	it('is not enabled when not in trashbin root', () => {
		vi.spyOn(ncInitialState, 'loadState').mockImplementationOnce(() => ({ allow_delete: true }))

		const root = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/other-folder', root: '/trashbin/test/' })
		const nodes = [
			new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/' }),
		]

		expect(emptyTrashAction.enabled).toBeTypeOf('function')
		expect(emptyTrashAction.enabled!(trashbinView, nodes, root)).toBe(false)
	})

	describe('execute', () => {
		const root = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/', root: '/trashbin/test/' })
		const nodes = [
			new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/' }),
		]

		let dialogBuilder = {
			setSeverity: vi.fn(),
			setText: vi.fn(),
			setButtons: vi.fn(),
			build: vi.fn(),
		}

		beforeEach(() => {
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
			const dialogSpy = vi.spyOn(ncDialogs, 'showInfo')

			dialogBuilder.build.mockImplementationOnce(() => ({ show: async () => false }))
			expect(await emptyTrashAction.exec(trashbinView, nodes, root)).toBe(null)
			expect(apiSpy).not.toBeCalled()
			expect(dialogSpy).toBeCalledWith('Deletion cancelled')
		})

		it('can cancel the deletion', async () => {
			const apiSpy = vi.spyOn(api, 'emptyTrash')
			const dialogSpy = vi.spyOn(ncDialogs, 'showInfo')

			dialogBuilder.build.mockImplementationOnce(() => ({
				show: async () => {
					const buttons = dialogBuilder.setButtons.mock.calls[0][0]
					const cancel = buttons.find(({ label }) => label === 'Cancel')
					await cancel.callback()
				},
			}))
			expect(await emptyTrashAction.exec(trashbinView, nodes, root)).toBe(null)
			expect(apiSpy).not.toBeCalled()
			expect(dialogSpy).toBeCalledWith('Deletion cancelled')
		})

		it('will trigger the API request if confirmed', async () => {
			const apiSpy = vi.spyOn(api, 'emptyTrash').mockImplementationOnce(async () => true)
			const dialogSpy = vi.spyOn(ncDialogs, 'showInfo')
			const eventBusSpy = vi.spyOn(ncEventBus, 'emit')

			dialogBuilder.build.mockImplementationOnce(() => ({
				show: async () => {
					const buttons = dialogBuilder.setButtons.mock.calls[0][0]
					const cancel = buttons.find(({ label }) => label === 'Empty deleted files')
					await cancel.callback()
				},
			}))
			expect(await emptyTrashAction.exec(trashbinView, nodes, root)).toBe(null)
			expect(apiSpy).toBeCalled()
			expect(dialogSpy).not.toBeCalled()
			expect(eventBusSpy).toBeCalledWith('files:node:deleted', nodes[0])
		})

		it('will not emit files deleted event if API request failed', async () => {
			const apiSpy = vi.spyOn(api, 'emptyTrash').mockImplementationOnce(async () => false)
			const dialogSpy = vi.spyOn(ncDialogs, 'showInfo')
			const eventBusSpy = vi.spyOn(ncEventBus, 'emit')

			dialogBuilder.build.mockImplementationOnce(() => ({
				show: async () => {
					const buttons = dialogBuilder.setButtons.mock.calls[0][0]
					const cancel = buttons.find(({ label }) => label === 'Empty deleted files')
					await cancel.callback()
				},
			}))
			expect(await emptyTrashAction.exec(trashbinView, nodes, root)).toBe(null)
			expect(apiSpy).toBeCalled()
			expect(dialogSpy).not.toBeCalled()
			expect(eventBusSpy).not.toBeCalled()
		})
	})
})
