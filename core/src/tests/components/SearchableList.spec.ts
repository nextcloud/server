/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { shallowMount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import SearchableList from '../../components/UnifiedSearch/SearchableList.vue'

function factory() {
	return shallowMount(SearchableList, {
		propsData: { searchList: [], emptyContentText: 'Nothing found' },
		global: { mocks: { t: (_: string, s: string) => s } },
	})
}

describe('SearchableList', () => {
	// The popover must keep its own focus trap so it registers on the shared trap stack
	// (window._nc_focus_trap) and coordinates with the unified-search modal trap. Setting
	// no-focus-trap opts it out; the modal trap then fights it for focus as it closes on
	// select, recursing (checkFocusIn <-> tryFocus) into "too much recursion".
	it('keeps its own focus trap so it joins the shared trap stack', () => {
		expect(factory().findComponent(NcPopover).props('noFocusTrap')).not.toBe(true)
	})

	// Vue 2.7 does not normalize v-on names, so these must match the modal's
	// kebab-case @item-selected / @search-term-change listeners exactly, or picking a
	// person and typing in the search silently do nothing.
	it('emits item-selected when an item is picked', () => {
		const wrapper = factory()
		const person = { id: 'u1', user: 'alice', displayName: 'Alice' }
		wrapper.vm.itemSelected(person)
		expect(wrapper.emitted('item-selected')?.[0]).toEqual([person])
	})

	it('emits search-term-change when the search term changes', () => {
		const wrapper = factory()
		wrapper.vm.searchTermChanged('bob')
		expect(wrapper.emitted('search-term-change')?.[0]).toEqual(['bob'])
	})
})
