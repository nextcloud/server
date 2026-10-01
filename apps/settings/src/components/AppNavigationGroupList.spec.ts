/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'
import NcAppNavigationCaption from '@nextcloud/vue/components/NcAppNavigationCaption'

vi.mock('vue-router/composables', async (importActual) => ({
	...(await importActual<object>()),
	useRoute: () => ({ params: {} }),
	useRouter: () => ({ push: vi.fn() }),
}))

vi.mock('../service/groups.ts', () => ({
	searchGroups: () => Promise.resolve([]),
}))

vi.mock('@vueuse/core', async (importActual) => ({
	...(await importActual<object>()),
	useElementVisibility: () => ref(false),
}))

import AppNavigationGroupList from './AppNavigationGroupList.vue'

describe('AppNavigationGroupList', () => {
	it('does not expose the group list as a heading (BITV 9.1.3.1a)', () => {
		// A testing pinia stubs the store actions, so this stays a focused
		// component test that controls its own data.
		const wrapper = mount(AppNavigationGroupList, {
			pinia: createTestingPinia({
				createSpy: vi.fn,
				initialState: { 'settings-users': { serverData: { isAdmin: false, isDelegatedAdmin: false } } },
			}),
		})

		// The sidebar group list is navigation, not document structure. It must
		// not emit a heading, which would sit before the page <h1> in the DOM
		// and produce an out-of-order outline (h2 before h1).
		const caption = wrapper.findComponent(NcAppNavigationCaption)
		expect(caption.exists()).toBe(true)
		expect(caption.find('h1,h2,h3,h4,h5,h6').exists()).toBe(false)

		// The "Groups" label is still rendered, just not as a heading.
		expect(caption.text()).toContain('Groups')
	})
})
