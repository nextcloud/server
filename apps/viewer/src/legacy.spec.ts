/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { LegacyViewerApi } from './legacy.ts'

import { beforeEach, describe, expect, it, vi } from 'vitest'

const warn = vi.fn()
const viewerOpen = vi.fn()
const viewerClose = vi.fn()
const viewerCompare = vi.fn()
const viewerOpenFolder = vi.fn()
// What the registry answers: a grouped handler for pictures, one on its own for text
const handlers = new Map([
	['images', { id: 'images', group: 'media', enabled: (nodes: Array<{ mime?: string }>) => nodes.every((node) => node.mime?.startsWith('image/')) }],
	['text', { id: 'text', enabled: (nodes: Array<{ mime?: string }>) => nodes.every((node) => node.mime === 'text/markdown') }],
])
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
	getViewer: () => ({ open: viewerOpen, openFolder: viewerOpenFolder, close: viewerClose, compare: viewerCompare }),
	canView: (node: unknown) => canViewMock(node),
	getHandlers: () => handlers,
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

const { installLegacyViewerApi } = await import('./legacy.ts')

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
		expect(warn.mock.calls[0][0]).toContain('removed in Nextcloud 39')
	})

	it('warns when a property is merely read', () => {
		expect(viewer().file).toBe('')
		expect(warn).toHaveBeenCalledOnce()
	})

	it('opens a file given by path, looking it up first', async () => {
		stat.mockResolvedValue({ data: { filename: '/files/emma/notes.md', basename: 'notes.md', mime: 'text/markdown', type: 'file', props: { fileid: 7 } } })

		await viewer().open({ path: '/notes.md' })

		expect(stat).toHaveBeenCalledWith('/files/emma/notes.md', expect.objectContaining({ details: true }))
		const [nodes, target] = viewerOpen.mock.calls[0]
		// A note is shown on its own, its folder not listed for it, as before
		expect(nodes).toHaveLength(1)
		expect(target.basename).toBe('notes.md')
		expect(viewerOpenFolder).not.toHaveBeenCalled()
	})

	// As the viewer app did for a picture, a video or a sound given no list
	it('pages through the folder of a picture opened alone', async () => {
		stat.mockResolvedValue({ data: { filename: '/files/emma/Holidays/x.jpg', basename: 'x.jpg', mime: 'image/jpeg', type: 'file', props: { fileid: 7 } } })

		await viewer().open({ path: '/Holidays/x.jpg', canLoop: false })

		expect(viewerOpen).not.toHaveBeenCalled()
		const [folder, target, options] = viewerOpenFolder.mock.calls[0]
		expect(folder.source).toMatch(/\/remote.php\/dav\/files\/emma\/Holidays$/)
		expect(folder.path).toBe('/Holidays')
		expect(target.basename).toBe('x.jpg')
		expect(options.canLoop).toBe(false)
	})

	it('keeps to the list it is given, without listing the folder', async () => {
		const info = { fileid: 1, filename: '/a.jpg', basename: 'a.jpg', mime: 'image/jpeg' }

		await viewer().open({ fileInfo: info, list: [info] })

		expect(viewerOpen).toHaveBeenCalledOnce()
		expect(viewerOpenFolder).not.toHaveBeenCalled()
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

		// Not on dav, so there is no folder to list for it
		expect(viewerOpenFolder).not.toHaveBeenCalled()
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
		const info = { fileid: 1, filename: '/a.jpg', mime: 'image/jpeg' }
		await viewer().open({ fileInfo: info, list: [info], onClose })

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
