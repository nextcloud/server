/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { describe, expect, it } from 'vitest'
import isSvg from 'is-svg'

import { deleted, deletedBy, originalLocation } from './columns'
import { TRASHBIN_VIEW_ID, trashbinView } from './trashbinView.ts'
import { getContents } from '../services/trashbin.ts'

describe('files_trasbin: trashbin files view', () => {
	it('has correct strings', () => {
		expect(trashbinView.id).toBe(TRASHBIN_VIEW_ID)
		expect(trashbinView.name).toBe('Deleted files')
		expect(trashbinView.caption).toBe('List of files that have been deleted.')
		expect(trashbinView.emptyTitle).toBe('No deleted files')
		expect(trashbinView.emptyCaption).toBe('Files and folders you have deleted will show up here')
	})

	it('sorts by deleted time', () => {
		expect(trashbinView.defaultSortKey).toBe('deleted')
	})

	it('is sticky to the bottom in the view list', () => {
		expect(trashbinView.sticky).toBe(true)
	})

	it('has order defined', () => {
		expect(trashbinView.order).toBeTypeOf('number')
		expect(trashbinView.order).toBe(50)
	})

	it('has valid icon', () => {
		expect(trashbinView.icon).toBeTypeOf('string')
		expect(isSvg(trashbinView.icon)).toBe(true)
	})

	it('has custom columns', () => {
		expect(trashbinView.columns).toHaveLength(3)
		expect(trashbinView.columns).toEqual([
			originalLocation,
			deletedBy,
			deleted,
		])
	})

	it('has get content method', () => {
		expect(trashbinView.getContents).toBeTypeOf('function')
		expect(trashbinView.getContents).toBe(getContents)
	})
})
