/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Folder } from '@nextcloud/files'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as ncEventBus from '@nextcloud/event-bus'
import isSvg from 'is-svg'

import { trashbinView } from '../files_views/trashbinView.ts'
import { restoreAction } from './restoreAction.ts'
import { PERMISSION_ALL, PERMISSION_NONE } from '../../../../core/src/OC/constants.js'

const axiosMock = vi.hoisted(() => ({
	request: vi.fn(),
}))
vi.mock('@nextcloud/axios', () => ({ default: axiosMock }))
vi.mock('@nextcloud/auth')

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
		expect(restoreAction.inline!(node, trashbinView)).toBe(true)
	})

	it('has the display name set', () => {
		const node = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/' })

		expect(restoreAction.displayName([node], trashbinView)).toBe('Restore')
	})

	it('has an icon set', () => {
		const node = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/' })

		const icon = restoreAction.iconSvgInline([node], trashbinView)
		expect(icon).toBeTypeOf('string')
		expect(isSvg(icon)).toBe(true)
	})

	it('is enabled for trashbin view', () => {
		const nodes = [
			new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/', permissions: PERMISSION_ALL }),
		]

		expect(restoreAction.enabled).toBeTypeOf('function')
		expect(restoreAction.enabled!(nodes, trashbinView)).toBe(true)
	})

	it('is not enabled when permissions are missing', () => {
		const nodes = [
			new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/', permissions: PERMISSION_NONE }),
		]

		expect(restoreAction.enabled).toBeTypeOf('function')
		expect(restoreAction.enabled!(nodes, trashbinView)).toBe(false)
	})

	it('is not enabled when no nodes are selected', () => {
		expect(restoreAction.enabled).toBeTypeOf('function')
		expect(restoreAction.enabled!([], trashbinView)).toBe(false)
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
		expect(restoreAction.enabled!(nodes, otherView)).toBe(false)
	})

	describe('execute', () => {
		beforeEach(() => {
			axiosMock.request.mockReset()
		})

		it('send restore request', async () => {
			const node = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/', permissions: PERMISSION_ALL })

			expect(await restoreAction.exec(node, trashbinView, '/')).toBe(true)
			expect(axiosMock.request).toBeCalled()
			expect(axiosMock.request.mock.calls[0][0].method).toBe('MOVE')
			expect(axiosMock.request.mock.calls[0][0].url).toBe(node.encodedSource)
			expect(axiosMock.request.mock.calls[0][0].headers.destination).toContain('/restore/')
		})

		it('deletes node from current view after successfull request', async () => {
			const node = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/', permissions: PERMISSION_ALL })

			const emitSpy = vi.spyOn(ncEventBus, 'emit')

			expect(await restoreAction.exec(node, trashbinView, '/')).toBe(true)
			expect(axiosMock.request).toBeCalled()
			expect(emitSpy).toBeCalled()
			expect(emitSpy).toBeCalledWith('files:node:deleted', node)
		})

		it('does not delete node from view if reuest failed', async () => {
			const node = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/', permissions: PERMISSION_ALL })

			axiosMock.request.mockImplementationOnce(() => { throw new Error() })
			const emitSpy = vi.spyOn(ncEventBus, 'emit')

			expect(await restoreAction.exec(node, trashbinView, '/')).toBe(false)
			expect(axiosMock.request).toBeCalled()
			expect(emitSpy).not.toBeCalled()
		})

		it('batch: only returns success if all requests worked', async () => {
			const node = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/', permissions: PERMISSION_ALL })

			expect(await restoreAction.execBatch!([node, node], trashbinView, '/')).toStrictEqual([true, true])
			expect(axiosMock.request).toBeCalledTimes(2)
		})

		it('batch: only returns success if all requests worked - one failed', async () => {
			const node = new Folder({ owner: 'test', source: 'https://example.com/remote.php/dav/trashbin/test/folder', root: '/trashbin/test/', permissions: PERMISSION_ALL })

			axiosMock.request.mockImplementationOnce(() => { throw new Error() })
			expect(await restoreAction.execBatch!([node, node], trashbinView, '/')).toStrictEqual([false, true])
			expect(axiosMock.request).toBeCalledTimes(2)
		})
	})
})
