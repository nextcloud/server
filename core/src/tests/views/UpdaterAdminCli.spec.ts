/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import { enableAutoUnmount, mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import UpdaterAdminCli from '../../views/UpdaterAdminCli.vue'

vi.mock('@nextcloud/initial-state', () => ({ loadState: vi.fn() }))

enableAutoUnmount(afterEach)

const docsUrl = 'https://example.org/cli-upgrade'
const overrideUrl = '?IKnowThatThisIsABigInstanceAndTheUpdateRequestCouldRunIntoATimeoutAndHowToRestoreABackup=IAmSuperSureToDoThis'

function render(tooBig: boolean) {
	vi.mocked(loadState).mockImplementation((app, key, fallback) => {
		if (app === 'core' && key === 'updateInfo') {
			return { tooBig, cliUpgradeLink: docsUrl }
		}
		return fallback
	})
	return mount(UpdaterAdminCli)
}

describe('UpdaterAdminCli', () => {
	it.each([true, false])('shows CLI guidance and documentation when tooBig is %s', (tooBig) => {
		const wrapper = render(tooBig)

		expect(wrapper.get('pre').text()).toBe('./occ upgrade')
		expect(wrapper.findAllComponents(NcButton).some((button) => button.props('href') === docsUrl)).toBe(true)
	})

	it('offers the web override for a large instance', () => {
		const wrapper = render(true)

		expect(wrapper.text()).toContain('more than 50 accounts')
		expect(wrapper.findComponent(NcNoteCard).exists()).toBe(true)
		expect(wrapper.findAllComponents(NcButton).some((button) => button.props('href') === overrideUrl)).toBe(true)
	})

	it('does not offer the web override when web updates are disabled', () => {
		const wrapper = render(false)

		expect(wrapper.text()).toContain('updating via browser is disabled')
		expect(wrapper.findComponent(NcNoteCard).exists()).toBe(false)
		expect(wrapper.findAllComponents(NcButton).some((button) => button.props('href') === overrideUrl)).toBe(false)
	})
})
