/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode, IView } from '@nextcloud/files'

import { showInfo } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { File, Folder } from '@nextcloud/files'
import { listen } from '@nextcloud/notify_push'
import { cleanup, render } from '@testing-library/vue'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent } from 'vue'
import { getChildrenEtags } from '../services/Files.ts'
import { getNodesById } from '../services/NodesApi.ts'
import { useActiveStore } from '../store/active.ts'
import { useFilesStore } from '../store/files.ts'
import { usePathsStore } from '../store/paths.ts'
import { MAX_CHANGED_NODES, useLiveFolderRefresh } from './useLiveFolderRefresh.ts'

const handlers = vi.hoisted(() => ({} as Record<string, (name: string, body: unknown) => void>))

vi.mock('@nextcloud/notify_push', () => ({
	listen: vi.fn((name: string, handler: (name: string, body: unknown) => void) => {
		handlers[name] = handler
		return true
	}),
}))
vi.mock('@nextcloud/auth', async (importOriginal) => ({
	...await importOriginal(),
	getCurrentUser: () => ({ uid: 'alice' }),
}))
vi.mock('@nextcloud/dialogs', () => ({ showInfo: vi.fn() }))
vi.mock('@nextcloud/sharing/public', () => ({ isPublicShare: () => false }))
vi.mock('@nextcloud/event-bus', async (importOriginal) => ({
	...await importOriginal(),
	emit: vi.fn(),
}))
vi.mock('../services/NodesApi.ts', () => ({ getNodesById: vi.fn() }))
vi.mock('../services/Files.ts', () => ({ getChildrenEtags: vi.fn() }))

const base = 'https://cloud.example.com/remote.php/dav/files/alice'
const goToRoute = vi.fn()
window.OCP = { Files: { Router: { params: { view: 'files' }, query: { dir: '/folder' }, goToRoute } } } as unknown as typeof window.OCP

/**
 * @param id - The file id
 * @param path - The path within the user files
 * @param attributes - The attributes
 */
function file(id: number, path: string, attributes: Record<string, unknown> = {}) {
	return new File({ id, owner: 'alice', source: `${base}${path}`, root: '/files/alice', mime: 'text/plain', attributes: { etag: `etag-${id}`, ...attributes } })
}

/**
 * @param id - The file id
 * @param path - The path within the user files
 */
function folderAt(id: number, path: string) {
	return new Folder({ id, owner: 'alice', source: `${base}${path}`.replace(/\/$/, ''), root: '/files/alice' })
}

const root = folderAt(1, '/')
const folder = folderAt(10, '/folder')
const report = file(11, '/folder/report.md')
const notes = file(12, '/folder/notes.md')

/**
 * Serve these nodes from the nodes API, filtered like the server does
 *
 * @param nodes - The nodes that exist
 */
function serve(nodes: INode[]) {
	vi.mocked(getNodesById).mockImplementation(async (fileIds, options = {}) => nodes.filter((node) => fileIds.includes(node.fileid!)
		&& (!options.dir || node.path === options.dir || node.path.startsWith(`${options.dir}/`))))
}

/**
 * Mount a component using the composable, with the given folder open
 *
 * @param current - The open folder
 * @param children - Its children
 * @param view - The current view id
 */
function mountList(current: Folder, children: INode[], view = 'files') {
	const filesStore = useFilesStore()
	filesStore.updateNodes([root, current, ...children])
	filesStore.setRoot({ service: 'files', root })
	usePathsStore().addPath({ service: 'files', path: current.path, source: current.source })
	;(current as Folder & { _children?: string[] })._children = children.map((node) => node.source)
	const activeStore = useActiveStore()
	activeStore.activeFolder = current
	activeStore.activeView = { id: view } as IView

	render(defineComponent({
		template: '<div />',
		setup: () => useLiveFolderRefresh(),
	}))
}

/**
 * @param fileIds - The pushed file ids
 */
function push(fileIds: number[]) {
	handlers.notify_file_id!('notify_file_id', fileIds)
}

/**
 * Let the queued refresh run
 */
async function settle() {
	await new Promise((resolve) => setTimeout(resolve, 0))
}

describe('composable: useLiveFolderRefresh', () => {
	beforeEach(() => {
		vi.mocked(emit).mockClear()
		vi.mocked(showInfo).mockClear()
		goToRoute.mockClear()
		vi.mocked(getNodesById).mockReset()
		vi.mocked(getChildrenEtags).mockReset()
		setActivePinia(createPinia())
		serve([root, folder, report, notes])
	})

	afterEach(cleanup)

	it('listens to file changes with their ids once', () => {
		mountList(folder, [report])
		mountList(folder, [report])

		expect(listen).toHaveBeenCalledTimes(2)
		expect(listen).toHaveBeenCalledWith('notify_file_id', expect.any(Function))
		expect(listen).toHaveBeenCalledWith('notify_file', expect.any(Function))
	})

	it('ignores changes outside of the current folder', async () => {
		mountList(folder, [report])

		// A change in a sibling folder of the root: the root is pushed, an unknown id too
		push([99, 1])
		await settle()

		// Only checks that the current folder is still there
		expect(getNodesById).toHaveBeenCalledOnce()
		expect(getNodesById).toHaveBeenCalledWith([10, 1])
		expect(emit).not.toHaveBeenCalled()
	})

	it('only fetches the changed children and the new nodes, with the folder', async () => {
		mountList(folder, [report, notes])
		const updated = file(11, '/folder/report.md', { etag: 'new-etag' })
		const created = file(20, '/folder/new.md')
		serve([root, folder, updated, created, notes])

		push([11, 20, 10])
		await settle()

		expect(getNodesById).toHaveBeenCalledOnce()
		expect(getNodesById).toHaveBeenCalledWith([10, 11, 20], { dir: '/folder' })
		expect(emit).toHaveBeenCalledWith('files:node:updated', updated)
		expect(emit).toHaveBeenCalledWith('files:node:created', created)
		expect(emit).toHaveBeenCalledTimes(2)
	})

	it('updates the current folder without event', async () => {
		mountList(folder, [report])
		const changed = folderAt(10, '/folder')
		changed.attributes.etag = 'new-etag'
		serve([root, changed, report])

		push([11, 10])
		await settle()

		expect(useFilesStore().getNode(folder.source)?.attributes.etag).toBe('new-etag')
		expect(emit).not.toHaveBeenCalledWith('files:node:updated', changed)
	})

	it('does not fetch unknown ids when the current folder did not change', async () => {
		mountList(folder, [report])

		// A new file in a subfolder: its parent is pushed, not the current folder
		push([30, 31])
		await settle()

		expect(getNodesById).not.toHaveBeenCalled()
	})

	it('keeps the attributes the nodes API does not provide yet', async () => {
		const known = file(11, '/folder/report.md', { 'comments-unread': 2 })
		mountList(folder, [known])
		const updated = file(11, '/folder/report.md', { etag: 'new-etag' })
		serve([root, folder, updated])

		push([11, 10])
		await settle()

		expect(emit).toHaveBeenCalledWith('files:node:updated', updated)
		expect(updated.attributes).toMatchObject({ etag: 'new-etag', 'comments-unread': 2 })
	})

	it('matches a file listed twice by path', async () => {
		const first = file(11, '/folder/report.md')
		const second = file(11, '/folder/report (2).md')
		mountList(folder, [first, second])
		// The second share of the file was removed
		serve([root, folder, first])

		push([11, 10])
		await settle()

		expect(emit).toHaveBeenCalledWith('files:node:updated', first)
		expect(emit).toHaveBeenCalledWith('files:node:deleted', second)
		expect(emit).toHaveBeenCalledTimes(2)
	})

	it('moves renamed children', async () => {
		mountList(folder, [report])
		const renamed = file(11, '/folder/final report.md')
		serve([root, folder, renamed])

		push([11, 10])
		await settle()

		expect(emit).toHaveBeenCalledWith('files:node:moved', { node: renamed, oldSource: report.source })
		expect(emit).not.toHaveBeenCalledWith('files:node:deleted', report)
	})

	it('ignores new nodes deeper in the folder', async () => {
		mountList(folder, [report])
		serve([root, folder, report, file(30, '/folder/sub/deep.md')])

		push([30, 10])
		await settle()

		expect(emit).not.toHaveBeenCalled()
	})

	it('deletes children that are not in the folder anymore', async () => {
		mountList(folder, [report, notes])
		serve([root, folder, report, file(12, '/elsewhere/notes.md')])

		push([12, 10, 1])
		await settle()

		expect(emit).toHaveBeenCalledWith('files:node:deleted', notes)
		expect(emit).toHaveBeenCalledTimes(1)
	})

	it('reconciles the folder when only the folder itself changed', async () => {
		mountList(folder, [report, notes])
		// notes.md went to the trash bin, older notify_push versions only push its parent
		vi.mocked(getChildrenEtags).mockResolvedValue(new Map([[11, 'etag-11']]))

		push([10])
		await settle()

		expect(getChildrenEtags).toHaveBeenCalledWith('/folder')
		expect(emit).toHaveBeenCalledWith('files:node:deleted', notes)
		expect(getNodesById).toHaveBeenCalledWith([10], { dir: '/folder' })
	})

	it('reconciles the folder when too many children changed', async () => {
		const children = Array.from({ length: MAX_CHANGED_NODES + 1 }, (_, index) => file(100 + index, `/folder/${index}.md`))
		mountList(folder, children)
		vi.mocked(getChildrenEtags).mockResolvedValue(new Map(children.map((child) => [child.fileid!, child.attributes.etag as string])))

		push([...children.map((child) => child.fileid!), 10])
		await settle()

		expect(getChildrenEtags).toHaveBeenCalledWith('/folder')
		expect(getNodesById).toHaveBeenCalledWith([10], { dir: '/folder' })
	})

	it('follows the current folder when it was moved', async () => {
		mountList(folder, [report])
		const moved = folderAt(10, '/archive/folder')
		serve([root, moved])

		push([1])
		await settle()

		expect(emit).toHaveBeenCalledWith('files:node:moved', { node: moved, oldSource: folder.source })
		expect(goToRoute).toHaveBeenCalledWith(null, { view: 'files', fileid: '10' }, { dir: '/archive/folder' })
	})

	it('goes to the closest ancestor when the current folder was deleted', async () => {
		mountList(folder, [report])
		serve([root])

		push([1])
		await settle()

		expect(emit).toHaveBeenCalledWith('files:node:deleted', folder)
		expect(showInfo).toHaveBeenCalledOnce()
		expect(goToRoute).toHaveBeenCalledWith(null, { view: 'files', fileid: '1' }, { dir: '/' })
	})

	it('waits for the page to be visible', async () => {
		const visibility = vi.spyOn(document, 'visibilityState', 'get').mockReturnValue('hidden')
		mountList(folder, [report])

		push([11, 10])
		await settle()
		expect(getNodesById).not.toHaveBeenCalled()

		visibility.mockReturnValue('visible')
		document.dispatchEvent(new Event('visibilitychange'))
		await settle()
		expect(getNodesById).toHaveBeenCalledWith([10, 11], { dir: '/folder' })
		visibility.mockRestore()
	})

	it('only refreshes the files view', async () => {
		mountList(folder, [report], 'recent')

		push([11, 10])
		await settle()

		expect(getNodesById).not.toHaveBeenCalled()
	})

	it('reconciles the root folder when shares changed', async () => {
		mountList(root, [folder])
		vi.mocked(getChildrenEtags).mockResolvedValue(new Map([[10, folder.attributes.etag as string]]))

		handlers.notify_file!('notify_file', null)
		await settle()

		expect(getChildrenEtags).toHaveBeenCalledWith('/')
	})

	it('ignores shares changes outside of the root folder', async () => {
		mountList(folder, [report])

		handlers.notify_file!('notify_file', null)
		await settle()

		expect(getChildrenEtags).not.toHaveBeenCalled()
	})

	it('drops pending changes when no list is mounted', async () => {
		const visibility = vi.spyOn(document, 'visibilityState', 'get').mockReturnValue('hidden')
		mountList(folder, [report])
		push([11, 10])
		cleanup()

		visibility.mockReturnValue('visible')
		mountList(folder, [report])
		document.dispatchEvent(new Event('visibilitychange'))
		await settle()

		expect(getNodesById).not.toHaveBeenCalled()
		visibility.mockRestore()
	})
})
