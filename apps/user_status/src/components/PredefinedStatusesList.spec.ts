/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { cleanup, render } from '@testing-library/vue'
import { createPinia } from 'pinia'
import { afterEach, describe, expect, it, vi } from 'vitest'
import PredefinedStatusesList from './PredefinedStatusesList.vue'
import { fetchAllPredefinedStatuses } from '../services/predefinedStatusService.js'
import { useUserStatusStore } from '../store/userStatus.ts'

vi.mock('../services/predefinedStatusService.js', () => ({
	fetchAllPredefinedStatuses: vi.fn(),
}))

describe('PredefinedStatusesList', () => {
	afterEach(cleanup)

	it('loads the predefined statuses and marks the current one as selected', async () => {
		vi.mocked(fetchAllPredefinedStatuses).mockResolvedValue([
			{ id: 'meeting', icon: '📅', message: 'In a meeting', clearAt: null },
			{ id: 'sick', icon: '🤒', message: 'Out sick', clearAt: null },
		])

		const pinia = createPinia()
		useUserStatusStore(pinia).loadStatusFromServer({ messageId: 'sick' })

		const component = render(PredefinedStatusesList, {
			global: { plugins: [pinia] },
		})

		const list = await component.findByRole('list', { name: 'Predefined statuses' })
		expect(list.textContent).toContain('In a meeting')
		expect(list.textContent).toContain('Out sick')
		expect(fetchAllPredefinedStatuses).toHaveBeenCalledOnce()
		expect(component.getByRole('radio', { checked: true, name: /Out sick/ })).toBeTruthy()
	})
})
