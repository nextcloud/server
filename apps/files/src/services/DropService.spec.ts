/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder } from '@nextcloud/files'
import type { RootDirectory } from './DropServiceUtils.ts'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { getUploader, hasConflict } from '@nextcloud/upload'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { onDropExternalFiles } from './DropService.ts'
import { createDirectoryIfNotExists, Directory } from './DropServiceUtils.ts'

vi.mock('@nextcloud/dialogs')
vi.mock('@nextcloud/upload', () => ({
	getUploader: vi.fn(),
	hasConflict: vi.fn(),
}))
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

describe('onDropExternalFiles', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		vi.mocked(getUploader).mockReturnValue({
			pause: vi.fn(),
			start: vi.fn(),
		} as never)
		vi.mocked(hasConflict).mockReturnValue(false)
	})

	it('rejects an invalid dropped tree before starting the upload', async () => {
		const root = new Directory('root', [new Directory('test\\')]) as RootDirectory

		const uploads = await onDropExternalFiles(root, {} as IFolder, [])

		expect(uploads).toEqual([])
		expect(showError).toHaveBeenCalledWith('Cannot upload "test\\": &quot;\\&quot; is not allowed inside a folder name.')
		expect(getUploader).not.toHaveBeenCalled()
		expect(hasConflict).not.toHaveBeenCalled()
	})

	it('does not report success after a directory creation failure', async () => {
		const root = new Directory('root', [new Directory('folder')]) as RootDirectory
		vi.mocked(createDirectoryIfNotExists).mockRejectedValue(new Error('Failed to create directory'))

		const uploads = await onDropExternalFiles(root, {} as IFolder, [])

		expect(uploads).toEqual([])
		expect(showError).toHaveBeenCalledWith('Unable to create the directory folder')
		expect(showSuccess).not.toHaveBeenCalled()
	})
})
