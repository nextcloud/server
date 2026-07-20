/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import NcListItem from '@nextcloud/vue/components/NcListItem'
import SearchResult from '../../components/UnifiedSearch/SearchResult.vue'

function factory(propsData = {}, attrs = {}) {
	return mount(SearchResult, {
		propsData: { title: 'A document', resourceUrl: '/f/1', thumbnailUrl: '', ...propsData },
		attrs,
	})
}

describe('SearchResult combobox option', () => {
	// aria-activedescendant on the input references the option element's id, and the
	// option is the <li> (decision 1B: per-group listboxes). The modal supplies the
	// role from context; the id comes from the prop. Both must land on the same <li>.
	it('carries the caller-supplied role and the id on the same option element', () => {
		const li = factory({ elementId: 'unified-search-result-files-0' }, { role: 'option' }).find('li')

		expect(li.attributes('role')).toBe('option')
		expect(li.attributes('id')).toBe('unified-search-result-files-0')
	})

	it('marks the active row through NcListItem so it is visually highlighted', () => {
		const wrapper = factory({ elementId: 'row-0', active: true })

		expect(wrapper.findComponent(NcListItem).props('active')).toBe(true)
	})

	it('leaves non-active rows unmarked', () => {
		const wrapper = factory({ elementId: 'row-0', active: false })

		expect(wrapper.findComponent(NcListItem).props('active')).toBe(false)
	})
})
