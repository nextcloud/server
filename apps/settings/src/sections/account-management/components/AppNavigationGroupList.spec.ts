/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { h, nextTick, ref } from 'vue'
import NcActionInput from '@nextcloud/vue/components/NcActionInput'
import NcAppNavigationCaption from '@nextcloud/vue/components/NcAppNavigationCaption'

const mocks = vi.hoisted(() => ({
	push: vi.fn(),
	searchGroups: vi.fn(),
	logError: vi.fn(),
}))

vi.mock('vue-router', async (importActual) => ({
	...(await importActual<object>()),
	useRoute: () => ({ params: {} }),
	useRouter: () => ({ push: mocks.push }),
}))

vi.mock('../../../service/groups.ts', () => ({
	searchGroups: mocks.searchGroups,
}))

vi.mock('../../../logger.ts', () => ({
	default: { error: mocks.logError, debug: vi.fn() },
}))

vi.mock('@vueuse/core', async (importActual) => ({
	...(await importActual<object>()),
	useElementVisibility: () => ref(false),
}))

import AppNavigationGroupList from './AppNavigationGroupList.vue'
import { useUsersStore } from '../store/users.ts'

/**
 * @param isAdmin Whether the current account is an admin
 * @param stubs Components to replace
 */
function mountList(isAdmin = false, stubs = {}) {
	return mount(AppNavigationGroupList, {
		global: {
			stubs,
			plugins: [createTestingPinia({
				createSpy: vi.fn,
				initialState: { 'settings-users': { serverData: { isAdmin, isDelegatedAdmin: false }, searchQuery: '' } },
			})],
		},
	})
}

describe('AppNavigationGroupList', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		mocks.searchGroups.mockResolvedValue([])
	})

	it('does not expose the group list as a heading (BITV 9.1.3.1a)', () => {
		// A testing pinia stubs the store actions, so this stays a focused
		// component test that controls its own data.
		const wrapper = mountList()

		// The sidebar group list is navigation, not document structure. It must
		// not emit a heading, which would sit before the page <h1> in the DOM
		// and produce an out-of-order outline (h2 before h1).
		const caption = wrapper.findComponent(NcAppNavigationCaption)
		expect(caption.exists()).toBe(true)
		expect(caption.find('h1,h2,h3,h4,h5,h6').exists()).toBe(false)

		// The "Groups" label is still rendered, just not as a heading.
		expect(caption.text()).toContain('Groups')
	})

	it('does not report a search that a newer one replaced', async () => {
		const wrapper = mountList(true)
		await flushPromises()
		mocks.searchGroups.mockImplementationOnce(({ signal }) => new Promise((resolve, reject) => {
			signal.addEventListener('abort', () => reject(Object.assign(new Error('canceled'), { __CANCEL__: true })))
		}))
		const store = useUsersStore()

		store.searchQuery = 'te'
		await nextTick()
		store.searchQuery = 'team'
		await flushPromises()

		expect(mocks.searchGroups).toHaveBeenLastCalledWith(expect.objectContaining({ search: 'team' }))
		expect(mocks.logError).not.toHaveBeenCalled()
		expect(wrapper.find('[role="note"]').exists()).toBe(false)
	})

	it('opens a created group', async () => {
		// The real caption only renders its actions while the menu is open
		const wrapper = mountList(true, {
			NcAppNavigationCaption: { render() {
				return h('div', this.$slots.actions?.())
			} },
		})
		const store = useUsersStore()
		vi.mocked(store.createGroup).mockResolvedValue(undefined)

		const input = wrapper.findComponent(NcActionInput)
		await input.vm.$emit('update:modelValue', 'Team & Co 50%')
		await input.vm.$emit('submit')
		await flushPromises()

		expect(store.createGroup).toHaveBeenCalledWith('Team & Co 50%')
		expect(mocks.push).toHaveBeenCalledWith({ name: 'group', params: { selectedGroup: 'Team%20%26%20Co%2050%25' } })
	})
})
