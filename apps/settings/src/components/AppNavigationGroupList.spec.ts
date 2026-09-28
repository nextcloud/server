/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import { describe, expect, it, vi } from 'vitest'
import NcAppNavigationCaption from '@nextcloud/vue/components/NcAppNavigationCaption'
import AppNavigationGroupList from './AppNavigationGroupList.vue'

// The component builds a real Vuex store via useStore(); mock it so this stays
// a focused component test that controls its own data.
vi.mock('../store/index.js', () => ({
	useStore: () => ({
		getters: {
			getServerData: { isAdmin: false, isDelegatedAdmin: false },
			getSortedGroups: [],
			getSubAdminGroups: [],
			getSearchQuery: '',
		},
		commit: vi.fn(),
		dispatch: vi.fn(),
	}),
}))

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

describe('AppNavigationGroupList', () => {
	it('does not expose the group list as a heading (BITV 9.1.3.1a)', () => {
		const wrapper = mount(AppNavigationGroupList)

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
