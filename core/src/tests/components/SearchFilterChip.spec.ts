/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { shallowMount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

// Substitute placeholders so the accessible name assertion is meaningful.
vi.mock('@nextcloud/l10n', () => ({
	t: (_app: string, text: string, vars?: Record<string, string>) => text.replace(/\{(\w+)\}/g, (_match, key) => vars?.[key] ?? `{${key}}`),
}))

import SearchFilterChip from '../../components/UnifiedSearch/SearchFilterChip.vue'

function factory(propsData = {}) {
	return shallowMount(SearchFilterChip, {
		propsData: { text: 'Photos', pretext: '', ...propsData },
	})
}

describe('SearchFilterChip remove control', () => {
	it('renders the remove control as a real button with an accessible name from the filter', () => {
		const button = factory().find('button')

		expect(button.exists()).toBe(true)
		// Keyboard users need a real, named control (the old markup was a bare <span>).
		expect(button.attributes('aria-label')).toBe('Remove filter: Photos')
	})

	it('emits delete (no payload) when the remove button is clicked', async () => {
		const wrapper = factory()

		await wrapper.find('button').trigger('click')

		expect(wrapper.emitted('delete')).toHaveLength(1)
		// The parent reads the filter from its own v-for scope, so no payload is sent.
		expect(wrapper.emitted('delete')![0]).toEqual([])
	})
})
