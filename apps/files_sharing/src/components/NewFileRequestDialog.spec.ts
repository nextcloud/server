/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it } from 'vitest'
import NewFileRequestDialog from './NewFileRequestDialog.vue'

/**
 * @param allowedFileTypes The allowed file types of the file request
 */
function attributesFor(allowedFileTypes: string[] | null) {
	return NewFileRequestDialog.computed.fileRequestAttributes.call({ allowedFileTypes } as never)
}

describe('NewFileRequestDialog.fileRequestAttributes', () => {
	it('marks the share as file request', () => {
		expect(attributesFor(null)).toEqual([{ value: true, key: 'enabled', scope: 'fileRequest' }])
	})

	it('ignores an empty file type limit', () => {
		expect(attributesFor([])).toEqual([{ value: true, key: 'enabled', scope: 'fileRequest' }])
	})

	it('adds the allowed file types', () => {
		expect(attributesFor(['.pdf', 'image/*'])).toEqual([
			{ value: true, key: 'enabled', scope: 'fileRequest' },
			{ value: ['.pdf', 'image/*'], key: 'allowed_types', scope: 'upload' },
		])
	})
})
