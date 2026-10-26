/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import api from './api.js'
import usersStore from './users.js'

vi.mock('./api.js', () => ({ default: { get: vi.fn() } }))

const { mutations, actions } = usersStore

describe('store:users addGroup', () => {
	it('inserts a new group filled up with defaults', () => {
		const state = { groups: [] }
		mutations.addGroup(state, { id: 'group1', name: 'Group One' })

		expect(state.groups).toEqual([
			{ id: 'group1', name: 'Group One', usercount: 0, disabled: 0, canAdd: true, canRemove: true },
		])
	})

	it('does not duplicate an existing group', () => {
		const state = {
			groups: [
				{ id: 'group1', name: 'Group One', usercount: 5, disabled: 1, canAdd: false, canRemove: false },
			],
		}
		mutations.addGroup(state, { id: 'group1', name: 'Group One' })

		expect(state.groups).toHaveLength(1)
	})

	it('upgrades a stub group once full details are known', () => {
		// e.g. committed first with only {id, name} from a users-details response
		const state = {
			groups: [
				{ id: 'group1', name: 'group1', usercount: 0, disabled: 0, canAdd: true, canRemove: true },
			],
		}

		mutations.addGroup(state, { id: 'group1', name: 'Group One', usercount: 5, disabled: 1, canAdd: false, canRemove: false })

		expect(state.groups).toEqual([
			{ id: 'group1', name: 'Group One', usercount: 5, disabled: 1, canAdd: false, canRemove: false },
		])
	})

	it('does not clobber known details with a later stub commit', () => {
		// e.g. the sidebar already loaded full details, then a users-details
		// response commits the same group again with only {id, name}
		const state = {
			groups: [
				{ id: 'group1', name: 'Group One', usercount: 5, disabled: 1, canAdd: false, canRemove: false },
			],
		}

		mutations.addGroup(state, { id: 'group1', name: 'Group One' })

		expect(state.groups).toEqual([
			{ id: 'group1', name: 'Group One', usercount: 5, disabled: 1, canAdd: false, canRemove: false },
		])
	})
})

describe('store:users getUsers', () => {
	const users = { alice: { id: 'alice' } }

	/**
	 * @param headers Response headers
	 */
	function respondWith(headers: Record<string, string>) {
		vi.mocked(api.get).mockResolvedValue({ headers, data: { ocs: { data: { users, groups: [] } } } })
	}

	beforeEach(() => {
		vi.mocked(api.get).mockReset()
	})

	it('requests only enabled users and continues from the offset in the Link header', async () => {
		respondWith({ link: '<https://cloud.example.com/ocs/v2.php/cloud/users/details?search=&enabled=1&limit=25&offset=42>; rel="next"' })
		const commit = vi.fn()

		await actions.getUsers({ commit }, { offset: 0, limit: 25, search: '', enabled: true })

		expect(vi.mocked(api.get).mock.calls[0][0]).toMatch(/cloud\/users\/details\?offset=0&limit=25&search=&enabled=1$/)
		expect(commit).toHaveBeenCalledWith('appendUsers', users)
		expect(commit).toHaveBeenCalledWith('setUsersOffset', 42)
	})

	it('requests only enabled users of a group', async () => {
		respondWith({})
		const commit = vi.fn()

		await actions.getUsers({ commit }, { offset: 0, limit: 25, search: '', group: 'group1', enabled: true })

		expect(vi.mocked(api.get).mock.calls[0][0]).toMatch(/cloud\/groups\/group1\/users\/details\?offset=0&limit=25&search=&enabled=1$/)
	})

	it('requests only disabled users', async () => {
		respondWith({})
		const commit = vi.fn()

		await actions.getUsers({ commit }, { offset: 0, limit: 25, search: '', enabled: false })

		expect(vi.mocked(api.get).mock.calls[0][0]).toMatch(/cloud\/users\/details\?offset=0&limit=25&search=&enabled=0$/)
	})

	it('keeps the offset when there is no next page', async () => {
		respondWith({})
		const commit = vi.fn()

		await actions.getUsers({ commit }, { offset: 0, limit: 25, search: '' })

		expect(vi.mocked(api.get).mock.calls[0][0]).not.toContain('enabled')
		expect(commit).not.toHaveBeenCalledWith('setUsersOffset', expect.anything())
	})
})

describe('store:users getRecentUsers', () => {
	beforeEach(() => {
		vi.mocked(api.get).mockReset()
	})

	it('requests only enabled users and continues from the offset in the Link header', async () => {
		vi.mocked(api.get).mockResolvedValue({
			headers: { link: '<https://cloud.example.com/ocs/v2.php/cloud/users/recent?search=&enabled=1&limit=25&offset=30>; rel="next"' },
			data: { ocs: { data: { users: { alice: { id: 'alice' } } } } },
		})
		const commit = vi.fn()

		await actions.getRecentUsers({ commit }, { offset: 0, limit: 25, search: '', enabled: true })

		expect(vi.mocked(api.get).mock.calls[0][0]).toMatch(/cloud\/users\/recent\?offset=0&limit=25&search=&enabled=1$/)
		expect(commit).toHaveBeenCalledWith('setUsersOffset', 30)
	})
})

describe('store:users setUsersOffset', () => {
	it('replaces the users offset', () => {
		const state = { usersOffset: 25 }
		mutations.setUsersOffset(state, 42)

		expect(state.usersOffset).toBe(42)
	})
})
