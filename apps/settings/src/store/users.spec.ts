/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it } from 'vitest'
import usersStore from './users.js'

const { mutations } = usersStore

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
