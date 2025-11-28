/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import * as ncEventBus from '@nextcloud/event-bus'
import { Folder } from '@nextcloud/files'
import isSvg from 'is-svg'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { trashbinView } from '../files_views/trashbinView.ts'
import { restoreAction } from './restoreAction.ts'

// TODO: once core is migrated to the new frontend use the import instead:
// import { PERMISSION_ALL, PERMISSION_NONE } from '../../../../core/src/OC/constants.js'
export const PERMISSION_NONE = 0
export const PERMISSION_ALL = 31

const axiosMock = vi.hoisted(() => ({
	request: vi.fn(),
}))
vi.mock('@nextcloud/axios', async (original) => ({ ...(await original()), default: axiosMock }))
vi.mock('@nextcloud/auth')

const errorSpy = vi.spyOn(window.console, 'error').mockImplementation(() => {})
beforeEach(() => {
	vi.resetAllMocks()
})

describe('files_trashbin: file actions - restore action', () => {
	it('has id set', () => {
		expect(restoreAction.id).toBe('restore')
	})

	it('has order set', () => {
		// very high priority!
		expect(restoreAction.order).toBe(1)
	})

	it('is an inline action', () => {
		const node = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/' })

		expect(restoreAction.inline).toBeTypeOf('function')
		expect(restoreAction.inline!({
			nodes: [node],
			view: trashbinView,
			folder: {} as Folder,
			contents: [],
		})).toBe(true)
	})

	it('has the display name set', () => {
		const node = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/' })

		expect(restoreAction.displayName({
			nodes: [node],
			view: trashbinView,
			folder: {} as Folder,
			contents: [],
		})).toBe('Restore')
	})

	it('has an icon set', () => {
		const node = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/' })

		const icon = restoreAction.iconSvgInline({
			nodes: [node],
			view: trashbinView,
			folder: {} as Folder,
			contents: [],
		})
		expect(icon).toBeTypeOf('string')
		expect(isSvg(icon)).toBe(true)
	})

	it('is enabled for trashbin view', () => {
		const nodes = [
			new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/', permissions: PERMISSION_ALL }),
		]

		expect(restoreAction.enabled).toBeTypeOf('function')
		expect(restoreAction.enabled!({
			nodes,
			view: trashbinView,
			folder: {} as Folder,
			contents: [],
		})).toBe(true)
	})

	it('is not enabled when permissions are missing', () => {
		const nodes = [
			new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/', permissions: PERMISSION_NONE }),
		]

		expect(restoreAction.enabled).toBeTypeOf('function')
		expect(restoreAction.enabled!({
			nodes,
			view: trashbinView,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})

	it('is not enabled when no nodes are selected', () => {
		expect(restoreAction.enabled).toBeTypeOf('function')
		expect(restoreAction.enabled!({
			nodes: [],
			view: trashbinView,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})

	it('is not enabled for other views', () => {
		const nodes = [
			new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/', permissions: PERMISSION_ALL }),
		]

		const otherView = new Proxy(trashbinView, {
			get(target, p) {
				if (p === 'id') {
					return 'other-view'
				}
				return target[p]
			},
		})

		expect(restoreAction.enabled).toBeTypeOf('function')
		expect(restoreAction.enabled!({
			nodes,
			view: otherView,
			folder: {} as Folder,
			contents: [],
		})).toBe(false)
	})

	describe('execute', () => {
		beforeEach(() => {
			axiosMock.request.mockReset()
		})

		it('send restore request', async () => {
			const node = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/', permissions: PERMISSION_ALL })

			expect(await restoreAction.exec({
				nodes: [node],
				view: trashbinView,
				folder: {} as Folder,
				contents: [],
			})).toBe(true)
			expect(axiosMock.request).toBeCalled()
			expect(axiosMock.request.mock.calls[0]![0].method).toBe('MOVE')
			expect(axiosMock.request.mock.calls[0]![0].url).toBe(node.encodedSource)
			expect(axiosMock.request.mock.calls[0]![0].headers.destination).toContain('/restore/')
		})

		it('deletes node from current view after successfull request', async () => {
			const node = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/', permissions: PERMISSION_ALL })

			const emitSpy = vi.spyOn(ncEventBus, 'emit')

			expect(await restoreAction.exec({
				nodes: [node],
				view: trashbinView,
				folder: {} as Folder,
				contents: [],
			})).toBe(true)
			expect(axiosMock.request).toBeCalled()
			expect(emitSpy).toBeCalled()
			expect(emitSpy).toBeCalledWith('files:node:deleted', node)
		})

		it('does not delete node from view if request failed', async () => {
			const node = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/', permissions: PERMISSION_ALL })

			axiosMock.request.mockImplementationOnce(() => {
				throw new Error()
			})
			const emitSpy = vi.spyOn(ncEventBus, 'emit')

			expect(await restoreAction.exec({
				nodes: [node],
				view: trashbinView,
				folder: {} as Folder,
				contents: [],
			})).toBe(false)
			expect(axiosMock.request).toBeCalled()
			expect(emitSpy).not.toBeCalled()
			expect(errorSpy).toBeCalled()
		})

		it('batch: only returns success if all requests worked', async () => {
			const node = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/', permissions: PERMISSION_ALL })

			expect(await restoreAction.execBatch!({
				nodes: [node, node],
				view: trashbinView,
				folder: {} as Folder,
				contents: [],
			})).toStrictEqual([true, true])
			expect(axiosMock.request).toBeCalledTimes(2)
		})

		it('batch: only returns success if all requests worked - one failed', async () => {
			const node = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/', permissions: PERMISSION_ALL })

			axiosMock.request.mockImplementationOnce(() => {
				throw new Error()
			})
			expect(await restoreAction.execBatch!({
				nodes: [node, node],
				view: trashbinView,
				folder: {} as Folder,
				contents: [],
			})).toStrictEqual([false, true])
			expect(axiosMock.request).toBeCalledTimes(2)
			expect(errorSpy).toBeCalled()
		})
	})
})
