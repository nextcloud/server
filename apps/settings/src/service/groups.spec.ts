/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it, vi } from 'vitest'
import { searchGroups } from './groups.ts'

const get = vi.hoisted(() => vi.fn())

vi.mock('@nextcloud/axios', () => ({ default: { get } }))

describe('searchGroups', () => {
	it('passes the abort signal and maps the groups', async () => {
		get.mockResolvedValue({
			data: { ocs: { data: { groups: [{ id: 'team', displayname: 'Team & Co', usercount: 3, disabled: 1, canAdd: true, canRemove: false }] } } },
		})
		const { signal } = new AbortController()

		const groups = await searchGroups({ search: 'Team & Co', offset: 25, limit: 25, signal })

		expect(get).toHaveBeenCalledWith(expect.stringContaining('/cloud/groups/details?search=Team%20%26%20Co&offset=25&limit=25'), { signal })
		expect(groups).toEqual([{ id: 'team', name: 'Team & Co', usercount: 3, disabled: 1, canAdd: true, canRemove: false }])
	})
})
