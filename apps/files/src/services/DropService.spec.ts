/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder } from '@nextcloud/files'

import { beforeEach, describe, expect, it, vi } from 'vitest'
import { onDropExternalFiles } from './DropService.ts'
import { createDirectoryIfNotExists, Directory } from './DropServiceUtils.ts'

const { getUploaderMock, hasConflictMock } = vi.hoisted(() => {
	return {
		getUploaderMock: vi.fn(),
		hasConflictMock: vi.fn(),
	}
})

vi.mock('@nextcloud/dialogs', () => ({
	showError: vi.fn(),
	showInfo: vi.fn(),
	showSuccess: vi.fn(),
	showWarning: vi.fn(),
}))

vi.mock('@nextcloud/upload', () => ({
	getUploader: getUploaderMock,
	hasConflict: hasConflictMock,
}))

vi.mock('../logger.ts', () => ({
	default: {
		debug: vi.fn(),
		info: vi.fn(),
		warn: vi.fn(),
		error: vi.fn(),
	},
}))

vi.mock('./DropServiceUtils.ts', async () => {
	const actual = await vi.importActual<typeof import('./DropServiceUtils.ts')>('./DropServiceUtils.ts')
	return {
		...actual,
		createDirectoryIfNotExists: vi.fn(),
		resolveConflict: vi.fn(async (files) => files),
	}
})

describe('DropService onDropExternalFiles', () => {
	const uploaderMock = {
		pause: vi.fn(),
		start: vi.fn(),
		upload: vi.fn(async () => ({})),
	}

	beforeEach(() => {
		vi.clearAllMocks()
		getUploaderMock.mockReturnValue(uploaderMock)
		hasConflictMock.mockResolvedValue(false)
	})

	it('creates dropped directories under the destination path', async () => {
		const root = new Directory('root') as Directory & { name: 'root' }
		root.contents = [
			new Directory('py-vetlog-buddy-main', [
				new Directory('.venv', [
					new Directory('lib', [
						new File(['content'], 'hello.txt', { type: 'text/plain' }),
					]),
				]),
			]),
		]

		const destination = {
			path: '/archive',
			source: '/remote.php/dav/files/devadmin/archive',
		} as unknown as IFolder

		await onDropExternalFiles(root, destination, [])

		expect(createDirectoryIfNotExists).toHaveBeenCalledWith('/archive/py-vetlog-buddy-main')
		expect(createDirectoryIfNotExists).toHaveBeenCalledWith('/archive/py-vetlog-buddy-main/.venv')
		expect(createDirectoryIfNotExists).toHaveBeenCalledWith('/archive/py-vetlog-buddy-main/.venv/lib')
	})
})
