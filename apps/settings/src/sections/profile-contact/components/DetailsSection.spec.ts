/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

let personalInfoParameters: Record<string, unknown>
vi.mock('@nextcloud/initial-state', () => ({
	loadState: () => personalInfoParameters,
}))

/**
 * The component reads its initial state at module scope, so it is imported freshly
 *
 * @param parameters Initial state overrides
 */
async function mountDetailsSection(parameters: Record<string, unknown> = {}) {
	personalInfoParameters = {
		groups: [],
		teams: [],
		quota: 1024,
		totalSpace: '1 KB',
		usage: '512 B',
		usageRelative: 50,
		...parameters,
	}
	const DetailsSection = await import('./DetailsSection.vue')
	return mount(DetailsSection.default)
}

describe('DetailsSection', () => {
	beforeEach(() => {
		vi.resetModules()
	})

	it('shows the usage of a limited quota', async () => {
		const wrapper = await mountDetailsSection()

		expect(wrapper.text()).toContain('You are using 512 B of 1 KB (50%)')
	})

	it('shows only the usage of an unlimited quota', async () => {
		const wrapper = await mountDetailsSection({ quota: -3 })

		expect(wrapper.text()).toContain('You are using 512 B')
		expect(wrapper.text()).not.toContain('1 KB')
	})

	it('lists groups and links teams', async () => {
		const wrapper = await mountDetailsSection({
			groups: ['admin', 'staff'],
			teams: [{ id: 'team', displayName: 'Design', link: 'https://example.com/team' }],
		})

		expect(wrapper.text()).toContain('admin, staff')
		expect(wrapper.find('a[href="https://example.com/team"]').text()).toContain('Design')
	})

	it('hides groups and teams the account is not part of', async () => {
		const wrapper = await mountDetailsSection()

		expect(wrapper.text()).not.toContain('Your groups')
		expect(wrapper.text()).not.toContain('Your teams')
	})
})
