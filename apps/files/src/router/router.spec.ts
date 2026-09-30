/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type VueRouter from 'vue-router'

import { emit } from '@nextcloud/event-bus'
import { File, Folder } from '@nextcloud/files'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import RouterService from '../services/RouterService.ts'
import { router } from './router.ts'

const stores = vi.hoisted(() => ({
	getPath: vi.fn(),
	getNode: vi.fn(),
}))

vi.mock('../store/paths.ts', () => ({
	usePathsStore: () => ({ getPath: stores.getPath }),
}))
vi.mock('../store/files.ts', () => ({
	useFilesStore: () => ({ getNode: stores.getNode }),
}))

const parentFolder = new Folder({
	id: 5,
	owner: 'test',
	root: '/files/test',
	source: 'https://cloud.example.com/remote.php/dav/files/test/folder',
})

const deletedFile = new File({
	id: 216,
	mime: 'text/plain',
	owner: 'test',
	root: '/files/test',
	source: 'https://cloud.example.com/remote.php/dav/files/test/folder/other',
})

describe('Router: deleting the current file', () => {
	let service: RouterService

	beforeEach(async () => {
		vi.clearAllMocks()
		stores.getPath.mockReturnValue(parentFolder.source)
		stores.getNode.mockReturnValue(parentFolder)

		service = new RouterService(router as unknown as VueRouter)
		window.OCP = { Files: { Router: service } } as unknown as typeof window.OCP

		await router.replace({
			name: 'filelist',
			params: { view: 'files', fileid: String(deletedFile.fileid) },
			query: { dir: '/folder', opendetails: 'true' },
		})
	})

	it('navigates to the parent folder', async () => {
		emit('files:node:deleted', deletedFile)
		await vi.waitUntil(() => router.currentRoute.value.params.fileid !== String(deletedFile.fileid))

		expect(stores.getPath).toHaveBeenCalledWith('files', '/folder')
		expect(router.currentRoute.value.params.fileid).toBe(String(parentFolder.fileid))
		expect(router.currentRoute.value.query).toEqual({ dir: '/folder' })
	})

	it('removes the fileid if the parent folder is unknown', async () => {
		stores.getNode.mockReturnValue(undefined)

		emit('files:node:deleted', deletedFile)
		await vi.waitUntil(() => router.currentRoute.value.params.fileid !== String(deletedFile.fileid))

		expect(router.currentRoute.value.params.fileid).toBeFalsy()
	})

	it('is not reverted by a navigation reacting to the same deletion', async () => {
		emit('files:node:deleted', deletedFile)
		// e.g. the sidebar store closes and syncs its open state to the route
		const query = { ...service.query }
		delete query.opendetails
		await service.goToRoute(null, { ...service.params }, query, true)

		expect(router.currentRoute.value.params.fileid).toBe(String(parentFolder.fileid))
		expect(router.currentRoute.value.query).toEqual({ dir: '/folder' })
	})

	it('ignores other nodes', async () => {
		const other = new File({
			id: 999,
			mime: 'text/plain',
			owner: 'test',
			root: '/files/test',
			source: `${parentFolder.source}/unrelated`,
		})

		emit('files:node:deleted', other)

		expect(stores.getPath).not.toHaveBeenCalled()
		expect(service.params.fileid).toBe(String(deletedFile.fileid))
	})
})
