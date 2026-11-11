/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IGroup, IUser } from '../views/user-types.d.ts'

import { showError } from '@nextcloud/dialogs'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { GroupSorting } from '../constants/GroupManagement.ts'
import { useUsersStore } from './users.ts'

const axios = vi.hoisted(() => ({
	get: vi.fn(),
	post: vi.fn(),
	put: vi.fn(),
	patch: vi.fn(),
	delete: vi.fn(),
	isCancel: vi.fn(() => false),
	CancelToken: { source: vi.fn(() => ({ token: {}, cancel: vi.fn() })) },
}))

vi.mock('@nextcloud/axios', () => ({ default: axios }))
vi.mock('@nextcloud/dialogs')
vi.mock('@nextcloud/password-confirmation', () => ({ confirmPassword: () => Promise.resolve() }))

/**
 * @param id The group id
 * @param usercount Members, including disabled ones
 * @param disabled Disabled members
 */
function group(id: string, usercount = 0, disabled = 0): IGroup {
	return { id, name: id, usercount, disabled, canAdd: true, canRemove: true }
}

function user(id: string, overrides: Partial<IUser> = {}): IUser {
	return { id, displayname: id, enabled: true, groups: [], subadmin: [], ...overrides } as IUser
}

function ocsResponse(data: unknown) {
	return { data: { ocs: { data } } }
}

describe('store:users', () => {
	let store: ReturnType<typeof useUsersStore>

	beforeEach(() => {
		setActivePinia(createPinia())
		vi.clearAllMocks()
		store = useUsersStore()
	})

	describe('addGroup', () => {
		it('inserts a new group filled up with defaults', () => {
			store.groups = []
			store.addGroup({ id: 'group1', name: 'Group One' })

			expect(store.groups).toEqual([
				{ id: 'group1', name: 'Group One', usercount: 0, disabled: 0, canAdd: true, canRemove: true },
			])
		})

		it('does not duplicate an existing group', () => {
			store.groups = [
				{ id: 'group1', name: 'Group One', usercount: 5, disabled: 1, canAdd: false, canRemove: false },
			]
			store.addGroup({ id: 'group1', name: 'Group One' })

			expect(store.groups).toHaveLength(1)
		})

		it('upgrades a stub group once full details are known', () => {
			// e.g. added first with only {id, name} from a users-details response
			store.groups = [
				{ id: 'group1', name: 'group1', usercount: 0, disabled: 0, canAdd: true, canRemove: true },
			]

			store.addGroup({ id: 'group1', name: 'Group One', usercount: 5, disabled: 1, canAdd: false, canRemove: false })

			expect(store.groups).toEqual([
				{ id: 'group1', name: 'Group One', usercount: 5, disabled: 1, canAdd: false, canRemove: false },
			])
		})

		it('does not clobber known details with a later stub', () => {
			// e.g. the sidebar already loaded full details, then a users-details
			// response adds the same group again with only {id, name}
			store.groups = [
				{ id: 'group1', name: 'Group One', usercount: 5, disabled: 1, canAdd: false, canRemove: false },
			]

			store.addGroup({ id: 'group1', name: 'Group One' })

			expect(store.groups).toEqual([
				{ id: 'group1', name: 'Group One', usercount: 5, disabled: 1, canAdd: false, canRemove: false },
			])
		})
	})

	describe('sortedGroups', () => {
		it('sorts by enabled members, then by name', () => {
			store.groups = [group('b', 3), group('a', 3), group('c', 5, 4), group('d', 9)]
			store.orderBy = GroupSorting.UserCount

			expect(store.sortedGroups.map(({ id }) => id)).toEqual(['d', 'a', 'b', 'c'])
		})

		it('sorts by name when configured', () => {
			store.groups = [group('b', 3), group('a', 1), group('c', 9)]
			store.orderBy = GroupSorting.GroupName

			expect(store.sortedGroups.map(({ id }) => id)).toEqual(['a', 'b', 'c'])
		})
	})

	describe('getUsers', () => {
		it('appends new users, advances the offset and learns their groups', async () => {
			store.groups = []
			store.users = [user('alice')]
			axios.get.mockResolvedValueOnce(ocsResponse({
				users: { alice: user('alice'), bob: user('bob') },
				groups: [{ id: 'team', displayname: 'The team' }],
			}))

			const count = await store.getUsers({ offset: 0, limit: 25 })

			expect(count).toBe(2)
			expect(store.users.map(({ id }) => id)).toEqual(['alice', 'bob'])
			expect(store.usersOffset).toBe(25)
			expect(store.groups).toEqual([{ ...group('team'), name: 'The team' }])
		})

		it('cancels the previous request when a new one starts', async () => {
			axios.get.mockResolvedValue(ocsResponse({ users: {} }))

			await store.getUsers({ offset: 0, limit: 25, search: 'a' })
			const first = axios.CancelToken.source.mock.results[0]!.value
			await store.getUsers({ offset: 0, limit: 25, search: 'ab' })

			expect(first.cancel).toHaveBeenCalledOnce()
		})
	})

	describe('enableDisableUser', () => {
		it('moves the user between the active and disabled counts', async () => {
			store.userCount = 2
			store.groups = [group('__nc_internal_recent', 2), group('disabled', 0), group('team', 2)]
			store.users = [user('alice', { groups: ['team'] })]
			axios.put.mockResolvedValueOnce({})

			await store.enableDisableUser({ userid: 'alice', enabled: false })

			expect(store.users[0]!.enabled).toBe(false)
			expect(store.userCount).toBe(1)
			expect(store.groups).toEqual([group('__nc_internal_recent', 1), group('disabled', 1), group('team', 2, 1)])
		})
	})

	describe('editUserMultiField', () => {
		it('merges the server response and keeps group counts in sync', async () => {
			store.userCount = 1
			store.groups = [group('old', 1), group('new', 0)]
			store.users = [user('alice', { groups: ['old'], displayname: 'Alice' })]
			axios.patch.mockResolvedValueOnce(ocsResponse({ groups: ['new'], displayname: 'Alice Doe' }))

			await store.editUserMultiField({ userid: 'alice', payload: { groups: ['new'], displayname: 'Alice Doe' } })

			expect(store.users[0]).toMatchObject({ groups: ['new'], displayname: 'Alice Doe' })
			expect(store.groups).toEqual([group('old', 0), group('new', 1)])
		})
	})

	describe('enabled filter and server-side paging', () => {
		const users = { alice: user('alice') }

		/**
		 * @param headers Response headers
		 */
		function respondWith(headers: Record<string, string>) {
			axios.get.mockResolvedValueOnce({ headers, ...ocsResponse({ users, groups: [] }) })
		}

		it('requests only enabled users and continues from the offset in the Link header', async () => {
			respondWith({ link: '<https://cloud.example.com/ocs/v2.php/cloud/users/details?search=&enabled=1&limit=25&offset=42>; rel="next"' })

			await store.getUsers({ offset: 0, limit: 25, search: '', enabled: true })

			expect(axios.get.mock.calls[0]![0]).toMatch(/cloud\/users\/details\?offset=0&limit=25&search=&enabled=1$/)
			expect(store.users.map(({ id }) => id)).toEqual(['alice'])
			expect(store.usersOffset).toBe(42)
		})

		it('requests only enabled users of a group', async () => {
			respondWith({})

			await store.getUsers({ offset: 0, limit: 25, search: '', group: 'group1', enabled: true })

			expect(axios.get.mock.calls[0]![0]).toMatch(/cloud\/groups\/group1\/users\/details\?offset=0&limit=25&search=&enabled=1$/)
		})

		it('requests only disabled users', async () => {
			respondWith({})

			await store.getUsers({ offset: 0, limit: 25, search: '', enabled: false })

			expect(axios.get.mock.calls[0]![0]).toMatch(/cloud\/users\/details\?offset=0&limit=25&search=&enabled=0$/)
		})

		it('keeps the offset when there is no next page', async () => {
			respondWith({})

			await store.getUsers({ offset: 0, limit: 25, search: '' })

			expect(axios.get.mock.calls[0]![0]).not.toContain('enabled')
			expect(store.usersOffset).toBe(25)
		})

		it('continues recent users from the offset in the Link header', async () => {
			axios.get.mockResolvedValueOnce({
				headers: { link: '<https://cloud.example.com/ocs/v2.php/cloud/users/recent?search=&enabled=1&limit=25&offset=30>; rel="next"' },
				...ocsResponse({ users }),
			})

			await store.getRecentUsers({ offset: 0, limit: 25, search: '', enabled: true })

			expect(axios.get.mock.calls[0]![0]).toMatch(/cloud\/users\/recent\?offset=0&limit=25&search=&enabled=1$/)
			expect(store.usersOffset).toBe(30)
		})
	})

	describe('request failures', () => {
		it('shows the server message and rethrows where the view needs it', async () => {
			const error = { response: { data: { ocs: { meta: { message: 'Group exists' } } } } }
			axios.post.mockRejectedValueOnce(error)

			await expect(store.createGroup('team')).rejects.toBe(error)
			expect(showError).toHaveBeenCalledWith(expect.stringContaining('<br>Group exists'), { isHTML: true })
		})

		it('falls back to a generic message without a server message', async () => {
			axios.delete.mockRejectedValueOnce(new Error('Network Error'))

			await store.removeGroup('team')

			expect(showError).toHaveBeenCalledWith('An error occurred during the request. Unable to proceed.')
		})
	})
})
