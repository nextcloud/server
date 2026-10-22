/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { afterAll, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest'
import SettingsNavigationItem from './SettingsNavigationItem.vue'

vi.mock('@nextcloud/router', () => ({
	generateUrl: (path: string, params: Record<string, string>) => path.replace(
		/\{(\w+)\}/g,
		(match, key) => params[key] ?? match,
	),
}))

const scrollIntoView = vi.fn()

beforeAll(() => {
	Object.defineProperty(Element.prototype, 'scrollIntoView', {
		configurable: true,
		value: scrollIntoView,
	})
})

beforeEach(() => {
	scrollIntoView.mockClear()
})

afterAll(() => {
	delete (Element.prototype as Partial<Element>).scrollIntoView
})

function mountItem(active: boolean) {
	return mount(SettingsNavigationItem, {
		propsData: {
			section: {
				id: 'overview',
				name: 'Overview',
				active,
			},
			type: 'admin',
		},
		stubs: {
			NcAppNavigationItem: { template: '<li><slot /></li>' },
		},
	})
}

describe('SettingsNavigationItem', () => {
	it('scrolls the active item into view', () => {
		mountItem(true)

		expect(scrollIntoView).toHaveBeenCalledWith({ block: 'nearest' })
	})

	it('does not scroll inactive items', () => {
		mountItem(false)

		expect(scrollIntoView).not.toHaveBeenCalled()
	})
})
