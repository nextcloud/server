/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { RootDirectory } from './DropServiceUtils.ts'

import { Folder, Permission } from '@nextcloud/files'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { Directory } from './DropServiceUtils.ts'

const uploader = {
	destination: null as Folder | null,
	pause: vi.fn(),
	start: vi.fn(),
	upload: vi.fn(),
}

vi.mock('@nextcloud/files/upload', () => ({
	getUploader: () => uploader,
}))

const { onDropExternalFiles } = await import('./DropService.ts')

/** Build a folder node for `/{name}` owned by the test user. */
function folder(name: string) {
	return new Folder({
		id: 1,
		owner: 'test',
		permissions: Permission.ALL,
		root: '/files/test',
		source: `https://cloud.example.com/remote.php/dav/files/test/${name}`,
	})
}

describe('onDropExternalFiles', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		uploader.destination = folder('current')
		uploader.upload.mockImplementation(async () => ({}))
	})

	it('uploads into the drop target and restores the uploader destination', async () => {
		const target = folder('subfolder')
		const root = new Directory('root') as RootDirectory
		root.contents.push(new File(['content'], 'dropped.txt'))
		uploader.upload.mockImplementation(async () => {
			// the destination has to point at the drop target while queueing
			expect(uploader.destination).toBe(target)
			return {}
		})

		await onDropExternalFiles(root, target, [])

		expect(uploader.upload).toHaveBeenCalledWith('/dropped.txt', expect.any(File))
		expect(uploader.destination?.source).toBe(folder('current').source)
		expect(uploader.start).toHaveBeenCalledOnce()
	})

	it('restores the uploader destination if queueing fails', async () => {
		const target = folder('subfolder')
		const root = new Directory('root') as RootDirectory
		root.contents.push(new File(['content'], 'dropped.txt'))
		uploader.upload.mockImplementation(() => {
			throw new Error('nope')
		})

		await expect(onDropExternalFiles(root, target, [])).rejects.toThrow('nope')

		expect(uploader.destination?.source).toBe(folder('current').source)
	})
})
