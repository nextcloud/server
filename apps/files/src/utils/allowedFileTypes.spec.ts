/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import { isPublicShare } from '@nextcloud/sharing/public'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { getAllowedFileTypes, isAllowedFileType } from './allowedFileTypes.ts'

vi.mock('@nextcloud/initial-state', () => ({
	loadState: vi.fn(),
}))
vi.mock('@nextcloud/sharing/public', () => ({
	isPublicShare: vi.fn(),
}))

describe('getAllowedFileTypes', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		vi.mocked(loadState).mockReturnValue(['.pdf'])
	})

	it('returns the allowed file types of a public share', () => {
		vi.mocked(isPublicShare).mockReturnValue(true)

		expect(getAllowedFileTypes()).toEqual(['.pdf'])
		expect(loadState).toHaveBeenCalledWith('files_sharing', 'allowedFileTypes', [])
	})

	it('does not restrict uploads outside of public shares', () => {
		vi.mocked(isPublicShare).mockReturnValue(false)

		expect(getAllowedFileTypes()).toEqual([])
		expect(loadState).not.toHaveBeenCalled()
	})
})

describe('isAllowedFileType', () => {
	it.each([
		['no restriction', [], 'run.exe', 'application/x-msdownload', true],
		['extension', ['.pdf'], 'doc.pdf', 'application/pdf', true],
		['extension is case insensitive', ['.PDF'], 'Doc.Pdf', 'application/pdf', true],
		['multi-part extension', ['.tar.gz'], 'archive.tar.gz', 'application/gzip', true],
		['extension mismatch', ['.pdf'], 'run.exe', 'application/x-msdownload', false],
		['mime type', ['application/pdf'], 'doc.pdf', 'application/pdf', true],
		['mime type mismatch is left to the server', ['application/pdf'], 'photo.jpg', 'image/jpeg', true],
		['mime type group', ['image/*', 'video/*'], 'clip.mp4', 'video/mp4', true],
		['mime type group mismatch is left to the server', ['image/*'], 'doc.pdf', 'application/pdf', true],
		['os specific mime type is left to the server', ['application/zip'], 'archive.zip', 'application/x-zip-compressed', true],
		['mixed list is left to the server', ['.pdf', 'image/*'], 'run.exe', 'application/x-msdownload', true],
		['unknown mime type is left to the server', ['image/*'], 'photo.heic', '', true],
		['unknown mime type is still checked by extension', ['.pdf'], 'notes.md', '', false],
	])('%s', (_, allowedFileTypes, name, type, expected) => {
		expect(isAllowedFileType(new File([], name, { type }), allowedFileTypes)).toBe(expected)
	})
})
