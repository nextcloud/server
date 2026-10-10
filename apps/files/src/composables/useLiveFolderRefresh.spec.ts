/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode, IView } from '@nextcloud/files'

import { emit } from '@nextcloud/event-bus'
import { File, Folder } from '@nextcloud/files'
import { listen } from '@nextcloud/notify_push'
import { cleanup, render } from '@testing-library/vue'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent } from 'vue'
import { getChildrenEtags } from '../services/Files.ts'
import { searchNodesById } from '../services/WebDavSearch.ts'
import { useActiveStore } from '../store/active.ts'
import { useFilesStore } from '../store/files.ts'
import { useLiveFolderRefresh } from './useLiveFolderRefresh.ts'

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
vi.mock('@nextcloud/sharing/public', () => ({ isPublicShare: () => false }))
vi.mock('@nextcloud/event-bus', async (importOriginal) => ({
	...await importOriginal(),
	emit: vi.fn(),
}))
vi.mock('../services/WebDavSearch.ts', () => ({
	MAX_SEARCH_FILE_IDS: 98,
	searchNodesById: vi.fn(),
}))
vi.mock('../services/Files.ts', () => ({ getChildrenEtags: vi.fn() }))

const base = 'https://cloud.example.com/remote.php/dav/files/alice'

/**
 * @param id - The file id
 * @param path - The path within the user files
 * @param etag - The etag
 */
function file(id: number, path: string, etag = `etag-${id}`) {
	return new File({ id, owner: 'alice', source: `${base}${path}`, root: '/files/alice', mime: 'text/plain', attributes: { etag } })
}

const folder = new Folder({ id: 10, owner: 'alice', source: `${base}/folder`, root: '/files/alice' })
const parent = new Folder({ id: 1, owner: 'alice', source: base, root: '/files/alice' })
const report = file(11, '/folder/report.md')
const notes = file(12, '/folder/notes.md')

/**
 * Mount a component using the composable, with the given folder open
 *
 * @param current - The open folder
 * @param children - Its children
 * @param view - The current view id
 */
function mountList(current: Folder, children: INode[], view = 'files') {
	const filesStore = useFilesStore()
	filesStore.updateNodes([parent, current, ...children])
	const activeStore = useActiveStore()
	;(current as Folder & { _children?: string[] })._children = children.map((node) => node.source)
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
		vi.mocked(searchNodesById).mockReset().mockResolvedValue([])
		vi.mocked(getChildrenEtags).mockReset()
		setActivePinia(createPinia())
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

		// A change in a sibling folder: only known ancestors are pushed
		push([1])
		await settle()

		expect(searchNodesById).not.toHaveBeenCalled()
		expect(getChildrenEtags).not.toHaveBeenCalled()
		expect(emit).not.toHaveBeenCalled()
	})

	it('only fetches changed children and new nodes', async () => {
		mountList(folder, [report, notes])
		const updated = file(11, '/folder/report.md', 'new-etag')
		const created = file(20, '/folder/new.md')
		vi.mocked(searchNodesById).mockResolvedValue([updated, created])

		// The file ids come along with their ancestors
		push([11, 20, 10, 1])
		await settle()

		expect(searchNodesById).toHaveBeenCalledOnce()
		expect(searchNodesById).toHaveBeenCalledWith([11, 20], { dir: '/folder' })
		expect(emit).toHaveBeenCalledWith('files:node:updated', updated)
		expect(emit).toHaveBeenCalledWith('files:node:created', created)
		expect(emit).toHaveBeenCalledTimes(2)
	})

	it('moves renamed children', async () => {
		mountList(folder, [report])
		const renamed = file(11, '/folder/final report.md')
		vi.mocked(searchNodesById).mockResolvedValue([renamed])

		push([11, 10])
		await settle()

		expect(emit).toHaveBeenCalledWith('files:node:moved', { node: renamed, oldSource: report.source })
	})

	it('ignores new nodes deeper in the folder', async () => {
		mountList(folder, [report])
		vi.mocked(searchNodesById).mockResolvedValue([file(30, '/folder/sub/deep.md')])

		push([30, 10])
		await settle()

		expect(emit).not.toHaveBeenCalled()
	})

	it('deletes children that are not in the folder anymore', async () => {
		mountList(folder, [report, notes])

		// Moved out of the folder: the search scoped to the folder does not find it
		push([12, 10, 1])
		await settle()

		expect(emit).toHaveBeenCalledWith('files:node:deleted', notes)
		expect(emit).toHaveBeenCalledTimes(1)
	})

	it('reconciles the folder when only the folder itself changed', async () => {
		mountList(folder, [report, notes])
		// notes.md was deleted, report.md changed and new.md was created
		vi.mocked(getChildrenEtags).mockResolvedValue(new Map([[11, 'new-etag'], [20, 'etag-20']]))
		const updated = file(11, '/folder/report.md', 'new-etag')
		const created = file(20, '/folder/new.md')
		vi.mocked(searchNodesById).mockResolvedValue([updated, created])

		// The trash bin drops the id of the deleted node, only the ancestors are pushed
		push([10, 1])
		await settle()

		expect(getChildrenEtags).toHaveBeenCalledWith('/folder')
		expect(emit).toHaveBeenCalledWith('files:node:deleted', notes)
		expect(searchNodesById).toHaveBeenCalledWith([11, 20], { dir: '/folder' })
		expect(emit).toHaveBeenCalledWith('files:node:updated', updated)
		expect(emit).toHaveBeenCalledWith('files:node:created', created)
	})

	it('waits for the page to be visible', async () => {
		const visibility = vi.spyOn(document, 'visibilityState', 'get').mockReturnValue('hidden')
		mountList(folder, [report])
		vi.mocked(searchNodesById).mockResolvedValue([file(11, '/folder/report.md', 'new-etag')])

		push([11, 10])
		await settle()
		expect(searchNodesById).not.toHaveBeenCalled()

		visibility.mockReturnValue('visible')
		document.dispatchEvent(new Event('visibilitychange'))
		await settle()
		expect(searchNodesById).toHaveBeenCalledWith([11], { dir: '/folder' })
		visibility.mockRestore()
	})

	it('only refreshes the files view', async () => {
		mountList(folder, [report], 'recent')

		push([11, 10])
		await settle()

		expect(searchNodesById).not.toHaveBeenCalled()
	})

	it('reconciles the root folder when shares changed', async () => {
		mountList(parent, [folder])
		vi.mocked(getChildrenEtags).mockResolvedValue(new Map([[10, folder.attributes.etag]]))

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

		expect(searchNodesById).not.toHaveBeenCalled()
		visibility.mockRestore()
	})
})
