/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { FileType } from '@nextcloud/files'
import type { Node } from '@nextcloud/files'
import { describe, expect, it, vi } from 'vitest'
import { getSummaryFor } from './fileUtils.ts'

// Mock translation function used in getSummaryFor
vi.mock('@nextcloud/l10n', () => ({
	n: vi.fn((app, single, plural, count) => {
		if (count === 1) return single.replace('%n', count)
		return plural.replace('%n', count)
	}),
}))

describe('fileUtils', () => {
	describe('getSummaryFor', () => {
		it('returns correct summary for files and folders', () => {
			const nodes = [
				{ type: FileType.File } as Node,
				{ type: FileType.Folder } as Node,
			]
			expect(getSummaryFor(nodes, 1)).toBe('1 file · 1 folder · 1 hidden')
		})

		it('does not display hidden string when hideHiddenString is true', () => {
			const nodes = [
				{ type: FileType.File } as Node,
				{ type: FileType.Folder } as Node,
			]
			expect(getSummaryFor(nodes, 1, true)).toBe('1 file · 1 folder')
		})

		it('returns correct summary when there are no hidden files', () => {
			const nodes = [
				{ type: FileType.File } as Node,
				{ type: FileType.File } as Node,
			]
			expect(getSummaryFor(nodes, 0)).toBe('2 files')
		})
	})
})
