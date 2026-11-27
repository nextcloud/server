/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import DashboardWidgetChoice from './DashboardWidgetChoice.vue'

const props = { id: 'choice', label: 'Status', selected: false, iconUrl: '/icon.svg', iconClass: 'icon-status' }

describe('DashboardWidgetChoice', () => {
	it('renders the icon slot instead of the icon props', () => {
		const wrapper = mount(DashboardWidgetChoice, { props, slots: { icon: '<svg class="custom-icon" />' } })
		const icon = wrapper.get('.widget-choice__icon--custom')
		expect(icon.attributes('aria-hidden')).toBe('true')
		expect(icon.find('.custom-icon').exists()).toBe(true)
		expect(wrapper.find('img').exists()).toBe(false)
		expect(wrapper.find('.icon-status').exists()).toBe(false)
	})

	it('falls back to the icon URL, then the icon class', async () => {
		const wrapper = mount(DashboardWidgetChoice, { props })
		expect(wrapper.get('img').attributes('src')).toBe('/icon.svg')
		expect(wrapper.find('.widget-choice__icon--custom').exists()).toBe(false)

		await wrapper.setProps({ iconUrl: '' })
		expect(wrapper.find('img').exists()).toBe(false)
		expect(wrapper.get('.widget-choice__icon').classes()).toContain('icon-status')
	})
})
