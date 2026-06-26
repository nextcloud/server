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

import AppItem from '../../components/AppItem.vue'

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

describe('core: AppItem', () => {
	it('renders the label', () => {
		const wrapper = mount(AppItem, { propsData: { app: makeApp({ name: 'Files' }) } })
		expect(wrapper.text()).toContain('Files')
	})

	it('active app has aria-current="page"', () => {
		const wrapper = mount(AppItem, { propsData: { app: makeApp({ active: true }) } })
		expect(wrapper.attributes('aria-current')).toBe('page')
	})
})
