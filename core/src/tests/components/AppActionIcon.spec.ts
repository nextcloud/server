/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AppActionIcon from '../../components/AppActionIcon.vue'

describe('core: AppActionIcon', () => {
	it('paints the icon from its URL', () => {
		const wrapper = mount(AppActionIcon, { propsData: { icon: '/core/img/actions/upload.svg' } })
		const icon = wrapper.get('.app-action-icon__img').element as HTMLElement
		expect(icon.style.getPropertyValue('--app-action-icon-url')).toBe('url("/core/img/actions/upload.svg")')
	})

	it('escapes the icon URL so it cannot break out of the url() token', () => {
		const wrapper = mount(AppActionIcon, { propsData: { icon: '/i".svg' } })
		const icon = wrapper.get('.app-action-icon__img').element as HTMLElement
		expect(icon.style.getPropertyValue('--app-action-icon-url')).toBe('url("/i\\".svg")')
	})

	it('renders the indicator only with a color', () => {
		const withColor = mount(AppActionIcon, { propsData: { icon: '/i.svg', color: 'rgb(0, 130, 201)' } })
		const indicator = withColor.get('.app-action-icon__indicator').element as HTMLElement
		expect(indicator.style.getPropertyValue('--app-action-icon-indicator-color')).toBe('rgb(0, 130, 201)')
		expect(indicator.querySelector('.plus-icon')).toBeTruthy()

		const withoutColor = mount(AppActionIcon, { propsData: { icon: '/i.svg' } })
		expect(withoutColor.find('.app-action-icon__indicator').exists()).toBe(false)
	})

	it('renders slotted icon components instead of the URL', () => {
		const wrapper = mount(AppActionIcon, { slots: { default: '<span class="inline-icon" />' } })
		expect(wrapper.find('.inline-icon').exists()).toBe(true)
		expect(wrapper.find('.app-action-icon__img').exists()).toBe(false)
	})
})
