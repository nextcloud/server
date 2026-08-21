/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INavigationEntry } from '../../types/navigation.d.ts'

import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

// Mock l10n for deterministic output; mirror real n() plural behavior.
vi.mock('@nextcloud/l10n', () => ({
	t: (_app: string, text: string) => text,
	n: (_app: string, singular: string, plural: string, count: number, vars?: Record<string, unknown>) => {
		const template = count === 1 ? singular : plural
		return template.replace(/\{count\}/g, String(vars?.count ?? count))
	},
}))

import AppMenuItem from '../../components/AppMenuItem.vue'

function makeApp(overrides: Partial<INavigationEntry> = {}): INavigationEntry {
	return {
		id: 'files',
		active: false,
		order: 0,
		href: '/apps/files',
		icon: '/apps/files/img/app.svg',
		type: 'link',
		name: 'Files',
		unread: 0,
		...overrides,
	}
}

describe('core: AppMenuItem', () => {
	it('renders the label', () => {
		const wrapper = mount(AppMenuItem, { propsData: { app: makeApp({ name: 'Files' }) } })
		expect(wrapper.text()).toContain('Files')
	})

	it('active app has aria-current="page"', () => {
		const wrapper = mount(AppMenuItem, { propsData: { app: makeApp({ active: true }) } })
		expect(wrapper.attributes('aria-current')).toBe('page')
	})

	it('renders an anchor for entries with a target', () => {
		const wrapper = mount(AppMenuItem, { propsData: { app: makeApp({ href: '/apps/files' }) } })
		expect(wrapper.element.tagName).toBe('A')
		expect(wrapper.attributes('href')).toBe('/apps/files')
	})

	it('renders a button for entries without a target', () => {
		const wrapper = mount(AppMenuItem, { propsData: { app: makeApp({ href: '' }) } })
		expect(wrapper.element.tagName).toBe('BUTTON')
		expect(wrapper.attributes('type')).toBe('button')
		expect(wrapper.attributes('href')).toBeUndefined()
	})

	it('renders the app icon by default and lets consumers replace it', () => {
		const wrapper = mount(AppMenuItem, { propsData: { app: makeApp() } })
		expect(wrapper.find('.app-icon').exists()).toBe(true)

		const slotted = mount(AppMenuItem, {
			propsData: { app: makeApp() },
			slots: { icon: '<span class="custom-icon" />' },
		})
		expect(slotted.find('.app-icon').exists()).toBe(false)
		expect(slotted.find('.custom-icon').exists()).toBe(true)
	})

	it('emits the activation to the parent', async () => {
		const wrapper = mount(AppMenuItem, { propsData: { app: makeApp({ href: '' }) } })
		await wrapper.trigger('click')
		expect(wrapper.emitted('click')).toHaveLength(1)
	})
})
