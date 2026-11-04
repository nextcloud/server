/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { cleanup, render } from '@testing-library/vue'
import { afterEach, describe, expect, it, vi } from 'vitest'

const initialState = vi.hoisted(() => ({
	sharingAppEnabled: true,
	sharingDocumentation: 'https://docs.example.com/admin-sharing',
}))

vi.mock('@nextcloud/initial-state', () => ({
	loadState: (_app: string, key: keyof typeof initialState) => initialState[key],
}))

import AdminSettingsSharing from './AdminSettingsSharing.vue'

const AdminSettingsSharingForm = { template: '<form aria-label="Sharing settings form" />' }

describe('AdminSettingsSharing', () => {
	afterEach(cleanup)

	it('shows the settings with their documentation', () => {
		initialState.sharingAppEnabled = true
		const { getByRole, queryByText } = render(AdminSettingsSharing, { global: { stubs: { AdminSettingsSharingForm } } })

		expect(getByRole('heading', { name: /Sharing/ })).toBeInTheDocument()
		expect(getByRole('link', { name: /External documentation/ })).toHaveAttribute('href', 'https://docs.example.com/admin-sharing')
		expect(getByRole('form', { name: 'Sharing settings form' })).toBeInTheDocument()
		expect(queryByText('You need to enable the File sharing App.')).toBeNull()
	})

	it('asks to enable the sharing app', () => {
		initialState.sharingAppEnabled = false
		const { getByText, queryByRole } = render(AdminSettingsSharing, { global: { stubs: { AdminSettingsSharingForm } } })

		expect(getByText('You need to enable the File sharing App.')).toBeInTheDocument()
		expect(queryByRole('form')).toBeNull()
	})
})
