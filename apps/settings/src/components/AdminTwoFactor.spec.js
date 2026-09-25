/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Vue from 'vue'
import Vuex, { Store } from 'vuex'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import AdminTwoFactor from './AdminTwoFactor.vue'

// Mirrors apps/settings/src/store/admin-security.js's shape - not imported
// directly since that module references the webpack-injected PRODUCTION
// global, which isn't defined under vitest.
Vue.use(Vuex)
const store = new Store({
	state: { enforced: false, enforcedGroups: [], excludedGroups: [] },
	mutations: {
		setEnforced(state, val) { state.enforced = val },
		setEnforcedGroups(state, val) { state.enforcedGroups = val },
		setExcludedGroups(state, val) { state.excludedGroups = val },
	},
})

vi.mock('@nextcloud/axios', () => ({
	default: {
		get: vi.fn(),
		put: vi.fn(),
	},
}))
vi.mock('@nextcloud/router', () => ({
	generateOcsUrl(url, params) {
		return url.replace(/\{(\w+)\}/g, (_, key) => encodeURIComponent(params[key] ?? ''))
	},
	generateUrl(url) {
		return url
	},
}))
vi.mock('@nextcloud/initial-state', () => ({
	loadState: () => '',
}))

function detailsResponse(groups) {
	return { data: { ocs: { data: { groups } } } }
}

function mountAdminTwoFactor() {
	return mount(AdminTwoFactor, {
		store,
		mocks: {
			t: (_app, text) => text,
		},
	})
}

describe('AdminTwoFactor', () => {
	beforeEach(() => {
		store.replaceState({ enforced: true, enforcedGroups: [], excludedGroups: [] })
	})

	afterEach(() => {
		vi.clearAllMocks()
	})

	it('resolves already-enforced/excluded groups to their real display name, even when absent from the first page', async () => {
		// Two missing groups, not one - a single missing group can't tell a
		// parallel Promise.all(...) apart from a debounced call that
		// collapses concurrent invocations down to the last one.
		store.replaceState({
			enforced: true,
			enforcedGroups: ['plugin_abc'],
			excludedGroups: ['plugin_xyz'],
		})
		axios.get.mockImplementation((url) => {
			if (url.includes('search=plugin_abc')) {
				return Promise.resolve(detailsResponse([{ id: 'plugin_abc', displayname: 'My Plugin Group' }]))
			}
			if (url.includes('search=plugin_xyz')) {
				return Promise.resolve(detailsResponse([{ id: 'plugin_xyz', displayname: 'My Other Plugin Group' }]))
			}
			// The general, unfiltered first page - deliberately doesn't include
			// either already-configured group, as if they sorted past the page cap.
			return Promise.resolve(detailsResponse([{ id: 'everyone', displayname: 'Everyone' }]))
		})

		const wrapper = mountAdminTwoFactor()

		await expect.poll(() => axios.get.mock.calls.length).toBe(3)
		await expect.poll(() => wrapper.vm.resolveGroup('plugin_abc').displayname).toBe('My Plugin Group')
		expect(wrapper.vm.resolveGroup('plugin_xyz').displayname).toBe('My Other Plugin Group')
	})

	it('fetches from the details endpoint (id + displayname), not the plain group-ID list', async () => {
		axios.get.mockResolvedValue(detailsResponse([{ id: 'plugin_abc', displayname: 'My Plugin Group' }]))

		const wrapper = mountAdminTwoFactor()

		await expect.poll(() => axios.get.mock.calls.length).toBeGreaterThan(0)
		expect(axios.get).toHaveBeenCalledWith(expect.stringContaining('cloud/groups/details'))
		await expect.poll(() => wrapper.vm.groups).toEqual([{ id: 'plugin_abc', displayname: 'My Plugin Group' }])
	})

	it('renders NcSelect options by display name, not the raw option object', async () => {
		axios.get.mockResolvedValue(detailsResponse([{ id: 'plugin_abc', displayname: 'My Plugin Group' }]))

		const wrapper = mountAdminTwoFactor()
		await expect.poll(() => wrapper.vm.groups.length).toBe(1)

		const selects = wrapper.findAll(NcSelect)
		expect(selects).toHaveLength(2)
		expect(selects.at(0).props('label')).toBe('displayname')
		expect(selects.at(1).props('label')).toBe('displayname')
	})

	// The getter/setter translation layer is new in this change, so this
	// guards the intermediate state (object-returning getters paired with a
	// saveChanges() that still read them directly) rather than a pre-fix
	// regression - reverting AdminTwoFactor.vue to origin/master passes this
	// test too, since the old getters already returned plain ID arrays.
	it('sends plain group ID arrays when saving, not display objects', async () => {
		store.replaceState({
			enforced: true,
			enforcedGroups: ['a', 'b'],
			excludedGroups: [],
		})
		axios.get.mockResolvedValue(detailsResponse([
			{ id: 'a', displayname: 'Group A' },
			{ id: 'b', displayname: 'Group B' },
		]))
		axios.put.mockResolvedValue({ data: {} })

		const wrapper = mountAdminTwoFactor()
		await expect.poll(() => wrapper.vm.groups.length).toBe(2)

		await wrapper.vm.saveChanges()

		expect(axios.put).toHaveBeenCalledWith(
			expect.any(String),
			expect.objectContaining({ enforcedGroups: ['a', 'b'], excludedGroups: [] }),
			expect.anything(),
		)
	})

	it('commits plain group IDs to Vuex when NcSelect emits a selection, not the display objects', async () => {
		axios.get.mockResolvedValue(detailsResponse([]))
		const wrapper = mountAdminTwoFactor()
		await expect.poll(() => axios.get.mock.calls.length).toBeGreaterThan(0)

		// Simulates NcSelect's v-model update - it emits the selected option
		// objects (matching :options="groups"), not plain IDs.
		wrapper.vm.enforcedGroups = [{ id: 'plugin_abc', displayname: 'My Plugin Group' }]

		expect(store.state.enforcedGroups).toEqual(['plugin_abc'])
	})
})
