/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import SearchResultSkeleton from '../../components/UnifiedSearch/SearchResultSkeleton.vue'

function factory(rows = 3) {
	return mount(SearchResultSkeleton, { propsData: { rows } })
}

describe('SearchResultSkeleton', () => {
	// The rows asked for, plus the heading the block always leads with.
	it('draws a heading bar above the rows', () => {
		expect(factory(3).findAll('.search-result-skeleton__bar')).toHaveLength(4)
	})

	it('is decoration: hidden from assistive tech and out of the tab order', () => {
		const wrapper = factory()

		expect(wrapper.attributes('aria-hidden')).toBe('true')
		// aria-hidden does not remove a focusable child from the tab order.
		expect(wrapper.findAll('a, button, input, [tabindex]')).toHaveLength(0)
	})
})
