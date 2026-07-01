/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it, vi } from 'vitest'
import { getFilenameValidity } from './filenameValidity.ts'

vi.mock('@nextcloud/capabilities', () => ({
	getCapabilities: () => ({
		files: {
			forbidden_filename_characters: ['/', '\\', '>'],
			forbidden_filenames: ['.htaccess'],
			forbidden_filename_basenames: ['con'],
			forbidden_filename_extensions: ['.exe', '.~'],
		},
	}),
}))

describe('getFilenameValidity', () => {
	it('returns no error for a valid filename', () => {
		expect(getFilenameValidity('valid-name.txt')).toBe('')
	})

	describe('empty name', () => {
		it('reports empty filename', () => {
			expect(getFilenameValidity('')).toBe('Filename must not be empty.')
		})

		it('reports empty filename for whitespace only', () => {
			expect(getFilenameValidity('   ')).toBe('Filename must not be empty.')
		})

		it('reports empty folder name when isFolder is set', () => {
			expect(getFilenameValidity('', false, true)).toBe('Folder name must not be empty.')
		})
	})

	describe('forbidden character', () => {
		it('reports forbidden character for a filename', () => {
			expect(getFilenameValidity('inva/lid')).toBe('"/" is not allowed inside a filename.')
		})

		it('reports forbidden character for a folder name', () => {
			expect(getFilenameValidity('inva/lid', false, true)).toBe('"/" is not allowed inside a folder name.')
		})
	})

	describe('reserved name', () => {
		it('reports a reserved filename', () => {
			expect(getFilenameValidity('.htaccess')).toBe('".htaccess" is a reserved name and not allowed for filenames.')
		})

		it('reports a reserved folder name', () => {
			expect(getFilenameValidity('.htaccess', false, true)).toBe('".htaccess" is a reserved name and not allowed for folder names.')
		})

		it('reports a reserved basename', () => {
			expect(getFilenameValidity('con.txt')).toBe('"con" is a reserved name and not allowed for filenames.')
		})
	})

	describe('forbidden extension', () => {
		it('reports a disallowed filetype when the extension looks like a real type', () => {
			expect(getFilenameValidity('virus.exe')).toBe('".exe" is not an allowed filetype.')
		})

		it('reports the extension as not allowed at the end of a filename when it is not a recognisable filetype', () => {
			// '.~' does not match /\.[a-z]/i, so the generic "must not end with" message is used.
			expect(getFilenameValidity('document.~')).toBe('Filenames must not end with ".~".')
		})

		it('reports the extension as not allowed for a folder name', () => {
			expect(getFilenameValidity('folder.exe', false, true)).toBe('Folder names must not end with ".exe".')
		})
	})

	describe('escape option', () => {
		it('does not affect the returned string for safe characters', () => {
			expect(getFilenameValidity('inva/lid', true)).toBe('"/" is not allowed inside a filename.')
		})

		it('escapes the matched character when requested', () => {
			expect(getFilenameValidity('inva>lid', true)).toBe('"&gt;" is not allowed inside a filename.')
		})

		it('does not escape the matched character by default', () => {
			expect(getFilenameValidity('inva>lid')).toBe('">" is not allowed inside a filename.')
		})
	})

	it('rethrows errors that are not InvalidFilenameError', async () => {
		const files = await import('@nextcloud/files')
		const spy = vi.spyOn(files, 'validateFilename').mockImplementation(() => {
			throw new Error('unexpected')
		})
		expect(() => getFilenameValidity('anything')).toThrow('unexpected')
		spy.mockRestore()
	})
})
