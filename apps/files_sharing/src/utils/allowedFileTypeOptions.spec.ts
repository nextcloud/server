/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import {
	createExtensionOption,
	getFileTypeOptions,
	isSelectableFileTypeOption,
	toFileTypeOptions,
} from './allowedFileTypeOptions.ts'

const originalOC = window.OC

describe('allowedFileTypeOptions', () => {
	beforeEach(() => {
		window.OC = {
			...window.OC,
			MimeTypeList: {
				aliases: {},
				names: {
					'image/png': 'PNG image',
					'application/pdf': 'PDF document',
				},
			},
		}
	})

	afterEach(() => {
		window.OC = originalOC
	})

	it('offers the MIME type groups first, then the MIME types sorted by name', () => {
		expect(getFileTypeOptions().map(({ id }) => id)).toEqual([
			'image/*',
			'video/*',
			'audio/*',
			'text/*',
			'application/pdf',
			'image/png',
		])
	})

	it('shows the MIME type next to its name', () => {
		expect(getFileTypeOptions()).toContainEqual({ id: 'application/pdf', label: 'PDF document (application/pdf)' })
		expect(getFileTypeOptions()).toContainEqual({ id: 'image/*', label: 'All images (image/*)' })
	})

	it('only offers the groups without a MIME type list', () => {
		window.OC = { ...window.OC, MimeTypeList: undefined }
		expect(getFileTypeOptions().map(({ id }) => id)).toEqual(['image/*', 'video/*', 'audio/*', 'text/*'])
	})

	it.each([
		['pdf', '.pdf'],
		['.PDF', '.pdf'],
		['*.pdf', '.pdf'],
		[' tar.gz ', '.tar.gz'],
	])('creates the extension option for "%s"', (text, extension) => {
		expect(createExtensionOption(text)).toEqual({ id: extension, label: extension })
	})

	it.each([
		['extension', '.pdf', true],
		['multi-part extension', '.tar.gz', true],
		['known MIME type', 'application/pdf', true],
		['known MIME type group', 'image/*', true],
		['dot only', '.', false],
		['extension with spaces', '.my pdf', false],
		['MIME type entered as text', '.image/png', false],
		['unknown MIME type', 'application/x-unknown', false],
	])('%s is selectable: %s', (_, id, expected) => {
		expect(isSelectableFileTypeOption({ id, label: id }, getFileTypeOptions())).toBe(expected)
	})

	it('keeps allowed file types that are not in the list', () => {
		expect(toFileTypeOptions(['application/pdf', '.dwg'], getFileTypeOptions())).toEqual([
			{ id: 'application/pdf', label: 'PDF document (application/pdf)' },
			{ id: '.dwg', label: '.dwg' },
		])
	})
})
