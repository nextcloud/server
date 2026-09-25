/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { LegacyViewerApi } from './viewer-legacy.ts'

import { beforeEach, describe, expect, it, vi } from 'vitest'

const warn = vi.fn()
const viewerOpen = vi.fn()
const viewerClose = vi.fn()
const viewerCompare = vi.fn()
const canViewMock = vi.fn<(node?: unknown) => boolean>(() => true)
const stat = vi.fn()

vi.mock('@nextcloud/logger', () => ({
	getLoggerBuilder: () => ({
		setApp: () => ({ detectUser: () => ({ build: () => ({ warn, debug: vi.fn() }) }) }),
	}),
}))

vi.mock('@nextcloud/auth', () => ({
	getCurrentUser: () => ({ uid: 'emma' }),
}))

vi.mock('@nextcloud/viewer', () => ({
	getViewer: () => ({ open: viewerOpen, close: viewerClose, compare: viewerCompare }),
	canView: (node: unknown) => canViewMock(node),
}))

vi.mock('@nextcloud/files/dav', async (orig) => {
	// eslint-disable-next-line @typescript-eslint/consistent-type-imports -- vitest importOriginal idiom
	const actual = await orig<typeof import('@nextcloud/files/dav')>()
	return {
		...actual,
		getRootPath: () => '/files/emma',
		getRemoteURL: () => 'https://cloud.example/remote.php/dav',
		getClient: () => ({ stat }),
	}
})

const { installLegacyViewerApi } = await import('./viewer-legacy.ts')

/** The global the shim installs */
function viewer(): LegacyViewerApi {
	return window.OCA.Viewer as LegacyViewerApi
}

describe('OCA.Viewer compatibility layer', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		canViewMock.mockReturnValue(true)
		installLegacyViewerApi()
	})

	it('warns on every use, not just the first', () => {
		viewer().close()
		viewer().close()
		expect(warn).toHaveBeenCalledTimes(2)
		expect(warn.mock.calls[0][0]).toContain('removed in Nextcloud 40')
	})

	it('warns when a property is merely read', () => {
		expect(viewer().file).toBe('')
		expect(warn).toHaveBeenCalledOnce()
	})

	it('opens a file given by path, looking it up first', async () => {
		stat.mockResolvedValue({ data: { filename: '/files/emma/x.jpg', basename: 'x.jpg', mime: 'image/jpeg', type: 'file', props: { fileid: 7 } } })

		await viewer().open({ path: '/x.jpg' })

		expect(stat).toHaveBeenCalledWith('/files/emma/x.jpg', expect.objectContaining({ details: true }))
		const [nodes, target] = viewerOpen.mock.calls[0]
		expect(nodes).toHaveLength(1)
		expect(target.basename).toBe('x.jpg')
	})

	it('opens the node from the list rather than a second copy of it', async () => {
		const info = { fileid: 1, filename: '/a.jpg', basename: 'a.jpg', mime: 'image/jpeg' }
		const other = { fileid: 2, filename: '/b.jpg', basename: 'b.jpg', mime: 'image/jpeg' }

		await viewer().open({ fileInfo: info, list: [info, other] })

		const [nodes, target] = viewerOpen.mock.calls[0]
		expect(nodes).toHaveLength(2)
		expect(nodes).toContain(target)
		expect(target.fileid).toBe(1)
	})

	it('keeps a file that the list does not hold', async () => {
		const info = { fileid: 1, filename: '/a.jpg', basename: 'a.jpg', mime: 'image/jpeg' }
		const other = { fileid: 2, filename: '/b.jpg', basename: 'b.jpg', mime: 'image/jpeg' }

		await viewer().open({ fileInfo: info, list: [other] })

		const [nodes, target] = viewerOpen.mock.calls[0]
		expect(nodes).toHaveLength(2)
		expect(nodes).toContain(target)
	})

	it('addresses a file served from outside dav by its own source', async () => {
		await viewer().open({
			fileInfo: { source: 'https://cloud.example/apps/pizza/topping/pineapple.jpg', basename: 'pineapple.jpg', mime: 'image/jpeg' },
		})

		const [, target] = viewerOpen.mock.calls[0]
		expect(target.source).toBe('https://cloud.example/apps/pizza/topping/pineapple.jpg')
		expect(target.basename).toBe('pineapple.jpg')
	})

	it('passes the handler on to openWith', async () => {
		await viewer().openWith('richdocuments', { fileInfo: { fileid: 1, filename: '/a.pdf', mime: 'application/pdf' } })
		expect(viewerOpen.mock.calls[0][3]).toBe('richdocuments')
	})

	it('answers the getters with what the last open was given', async () => {
		const loadMore = () => []
		await viewer().open({
			fileInfo: { fileid: 1, filename: '/a.jpg', mime: 'image/jpeg' },
			list: [{ fileid: 1, filename: '/a.jpg', mime: 'image/jpeg' }],
			enableSidebar: false,
			canLoop: false,
			loadMore,
		})

		expect(viewer().file).toBe('/a.jpg')
		expect(viewer().enableSidebar).toBe(false)
		expect(viewer().canLoop).toBe(false)
		expect(viewer().loadMore).toBe(loadMore)
		// list was never a real getter on the viewer app, so it always read as
		// undefined; it answers with the files now rather than with nothing
		expect(viewer().list).toHaveLength(1)
	})

	it('forgets the file once closed', async () => {
		await viewer().open({ fileInfo: { fileid: 1, filename: '/a.jpg', mime: 'image/jpeg' } })
		expect(viewer().file).toBe('/a.jpg')

		viewer().close()

		expect(viewerClose).toHaveBeenCalledOnce()
		expect(viewer().file).toBe('')
	})

	it('forgets the file when the viewer closes itself', async () => {
		const onClose = vi.fn()
		await viewer().open({ fileInfo: { fileid: 1, filename: '/a.jpg', mime: 'image/jpeg' }, onClose })

		// the viewer calls back rather than being told
		viewerOpen.mock.calls[0][2].onClose()

		expect(onClose).toHaveBeenCalledOnce()
		expect(viewer().file).toBe('')
	})

	it('answers mimetypes by asking the handlers', () => {
		canViewMock.mockReturnValue(true)
		expect(viewer().mimetypes.includes('image/jpeg')).toBe(true)
		expect(viewer().mimetypes.indexOf('image/jpeg')).not.toBe(-1)

		canViewMock.mockReturnValue(false)
		expect(viewer().mimetypes.includes('application/x-nothing')).toBe(false)
		expect(viewer().mimetypes.indexOf('application/x-nothing')).toBe(-1)
	})

	it('still refuses what the viewer app refused', async () => {
		await expect(viewer().open({})).rejects.toThrow('needs either an URL or path')
		await expect(viewer().open({ path: 'relative.jpg' })).rejects.toThrow('absolute path')
	})
})
