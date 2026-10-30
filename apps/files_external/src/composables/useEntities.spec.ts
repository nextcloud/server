/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { flushPromises } from '@vue/test-utils'
import { beforeEach, describe, expect, test, vi } from 'vitest'
import { effectScope, nextTick, ref } from 'vue'

const axios = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn() }))
vi.mock('@nextcloud/axios', () => ({ default: axios }))

const { useGroups, useUsers } = await import('./useEntities.ts')

describe('useUsers', () => {
	beforeEach(() => {
		vi.resetAllMocks()
	})

	test('resolves display names for given uids', async () => {
		axios.post.mockResolvedValue({ data: { users: { alice: 'Alice Doe' } } })

		let users
		effectScope().run(() => {
			users = useUsers(() => ['alice'])
		})
		await flushPromises()

		expect(axios.post).toHaveBeenCalledWith(expect.stringContaining('/displaynames'), { users: ['alice'] })
		expect(users!.value).toEqual([{ id: 'user:alice', user: 'alice', displayName: 'Alice Doe' }])
	})
})

describe('useGroups', () => {
	beforeEach(() => {
		vi.resetAllMocks()
	})

	test('resolves display names for given gids via the applicable endpoint', async () => {
		axios.get.mockResolvedValue({ data: { groups: { grp1: 'Marketing Team' } } })

		let groups
		effectScope().run(() => {
			groups = useGroups(() => ['grp1'])
		})
		await flushPromises()

		expect(axios.get).toHaveBeenCalledWith(
			expect.stringContaining('applicable'),
			{ params: { pattern: 'grp1', limit: 50 } },
		)
		expect(groups!.value[0]).toMatchObject({ id: 'grp1', displayName: 'Marketing Team' })
	})

	test('falls back to the raw gid when the group is unknown', async () => {
		axios.get.mockResolvedValue({ data: { groups: {} } })

		let groups
		effectScope().run(() => {
			groups = useGroups(() => ['deleted-group'])
		})
		await flushPromises()

		expect(groups!.value[0]).toMatchObject({ id: 'deleted-group', displayName: 'deleted-group' })
	})

	test('does not repeat a request for a gid still waiting its turn when the list shrinks back', async () => {
		const resolvers: Record<string, (value: { data: { groups: Record<string, string> } }) => void> = {}
		axios.get.mockImplementation((_url: string, config: { params: { pattern: string } }) => new Promise((resolve) => {
			resolvers[config.params.pattern] = resolve
		}))

		// missingGroups is computed as [a, b]; 'a' starts its request immediately,
		// 'b' is still queued behind it in the loop (not requested yet)
		const gids = ref(['a', 'b'])
		let groups
		effectScope().run(() => {
			groups = useGroups(gids)
		})
		await nextTick()
		expect(axios.get).toHaveBeenCalledTimes(1)

		// list shrinks back to just 'a' while both 'a' and 'b' are already marked pending
		gids.value = ['a']
		await nextTick()
		expect(axios.get).toHaveBeenCalledTimes(1)

		resolvers.a({ data: { groups: { a: 'A Team' } } })
		await flushPromises()

		// 'b' still gets its request once its turn in the loop comes up, just not a duplicate one
		expect(axios.get).toHaveBeenCalledTimes(2)

		resolvers.b({ data: { groups: { b: 'B Team' } } })
		await flushPromises()

		expect(axios.get).toHaveBeenCalledTimes(2)
	})
})
