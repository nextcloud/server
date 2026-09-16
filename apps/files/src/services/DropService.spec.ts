/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder } from '@nextcloud/files'
import type { RootDirectory } from './DropServiceUtils.ts'

import { showError, showInfo, showSuccess } from '@nextcloud/dialogs'
import { Folder, Permission } from '@nextcloud/files'
import { getUploader } from '@nextcloud/files/upload'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { getConflicts } from '../utils/conflicts.ts'
import { newNodeName } from '../utils/newNodeDialog.ts'
import { onDropExternalFiles } from './DropService.ts'
import { createDirectoryIfNotExists, Directory } from './DropServiceUtils.ts'

const uploader = vi.hoisted(() => ({
	destination: null as Folder | null,
	pause: vi.fn(),
	start: vi.fn(),
	upload: vi.fn(),
}))

vi.mock('@nextcloud/dialogs')
vi.mock('@nextcloud/files/upload', () => ({
	getUploader: vi.fn(() => uploader),
}))
vi.mock('../utils/conflicts.ts', () => ({
	getConflicts: vi.fn(),
}))
vi.mock('../utils/newNodeDialog.ts')
vi.mock('./DropServiceUtils.ts', async (importOriginal) => ({
	...await importOriginal(),
	createDirectoryIfNotExists: vi.fn(),
}))
vi.mock('@nextcloud/capabilities', () => ({
	getCapabilities: () => ({
		files: {
			forbidden_filename_characters: ['/', '\\'],
			forbidden_filenames: ['.htaccess'],
			forbidden_filename_basenames: [],
			forbidden_filename_extensions: ['.part'],
		},
	}),
}))

/**
 * Build a folder node for `/{name}` owned by the test user.
 *
 * @param name - The folder name
 */
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
		vi.mocked(getConflicts).mockReturnValue([])
		uploader.destination = folder('current')
		uploader.upload.mockImplementation(async () => ({}))
	})

	it('asks to rename an invalid entry before starting the upload', async () => {
		const root = new Directory('root', [new Directory('test\\')]) as RootDirectory
		vi.mocked(newNodeName).mockResolvedValue('test')

		await onDropExternalFiles(root, {} as IFolder, [])

		expect(newNodeName).toHaveBeenCalledWith('test\\', [], expect.objectContaining({ isFolder: true }))
		expect(root.contents[0].name).toBe('test')
	})

	it('aborts the upload if the rename is cancelled', async () => {
		const root = new Directory('root', [new Directory('test\\')]) as RootDirectory
		vi.mocked(newNodeName).mockResolvedValue(null)

		const uploads = await onDropExternalFiles(root, {} as IFolder, [])

		expect(uploads).toEqual([])
		expect(showInfo).toHaveBeenCalledWith('Upload cancelled, drop the files again to retry')
		expect(getUploader).not.toHaveBeenCalled()
		expect(getConflicts).not.toHaveBeenCalled()
	})

	it('does not report success after a directory creation failure', async () => {
		const root = new Directory('root', [new Directory('folder')]) as RootDirectory
		vi.mocked(createDirectoryIfNotExists).mockRejectedValue(new Error('Failed to create directory'))

		const uploads = await onDropExternalFiles(root, {} as IFolder, [])

		expect(uploads).toEqual([])
		expect(showError).toHaveBeenCalledWith('Unable to create the directory folder')
		expect(showSuccess).not.toHaveBeenCalled()
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
