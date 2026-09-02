/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@nextcloud/l10n', () => ({
	t: (_app: string, text: string) => text,
}))
vi.mock('@nextcloud/router', () => ({
	generateUrl: (path: string) => path,
}))

import ConnectedServicesBar from '../../components/UnifiedSearch/ConnectedServicesBar.vue'

function factory(active = false) {
	return mount(ConnectedServicesBar, { propsData: { active } })
}

describe('ConnectedServicesBar', () => {
	// Matched by label, not position, so it still finds the toggle if the gear ever
	// stops rendering as an <a>.
	const toggle = (wrapper: ReturnType<typeof factory>) => wrapper.findAll('button').wrappers
		.find((button) => button.text().includes('connected services'))!

	it('offers to opt in while connected services are off', () => {
		expect(factory().text()).toContain('More from connected services')
	})

	it('offers to opt back out once they are on', () => {
		expect(factory(true).text()).toContain('Less from connected services')
	})

	it('asks the parent to flip the opt-in', async () => {
		const wrapper = factory()

		await toggle(wrapper).trigger('click')

		expect(wrapper.emitted('toggle')).toHaveLength(1)
	})

	it('links to the connected accounts settings in a new tab', () => {
		const link = factory().find('a')

		expect(link.attributes('href')).toBe('/settings/user/connected-accounts')
		expect(link.attributes('target')).toBe('_blank')
		expect(link.attributes('aria-label')).toBe('Connected services settings')
	})
})
