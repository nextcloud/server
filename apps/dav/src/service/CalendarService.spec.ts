/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { afterEach, describe, expect, it, vi } from 'vitest'
import { getEmptySlots, saveScheduleInboxAvailability } from './CalendarService.ts'

const customRequest = vi.fn()
vi.mock('../dav/client.ts', () => ({
	getClient: () => ({ customRequest }),
}))

afterEach(() => {
	customRequest.mockReset()
})

describe('saveScheduleInboxAvailability', () => {
	it('removes the availability property when there are no slots', async () => {
		await saveScheduleInboxAvailability(getEmptySlots(), 'UTC')

		expect(customRequest).toHaveBeenCalledOnce()
		const [path, { method, data }] = customRequest.mock.calls[0]
		expect(path).toBe('inbox')
		expect(method).toBe('PROPPATCH')
		expect(data).toContain('<x0:remove>')
		expect(data).not.toContain('<x0:set>')
		expect(data).not.toContain('VAVAILABILITY')
	})

	it('sets the availability property when there are slots', async () => {
		const slots = getEmptySlots()
		slots.MO = [{
			start: new Date('2026-10-12T09:00:00Z').getTime() / 1000,
			end: new Date('2026-10-12T17:00:00Z').getTime() / 1000,
		}]

		await saveScheduleInboxAvailability(slots, 'UTC')

		expect(customRequest).toHaveBeenCalledOnce()
		const [path, { method, data }] = customRequest.mock.calls[0]
		expect(path).toBe('inbox')
		expect(method).toBe('PROPPATCH')
		expect(data).toContain('<x0:set>')
		expect(data).toContain('BEGIN:VAVAILABILITY')
		expect(data).toContain('BEGIN:AVAILABLE')
	})
})
