/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import { handleError, ocsData } from './handlers.ts'

const mocks = vi.hoisted(() => ({
	showError: vi.fn(),
}))

vi.mock('@nextcloud/dialogs', () => ({ showError: mocks.showError }))
vi.mock('../logger.ts', () => ({ default: { error: vi.fn() } }))

/**
 * @param status HTTP status of the failed response
 */
function axiosError(status: number) {
	return { isAxiosError: true, response: { status } }
}

describe('handleError', () => {
	beforeEach(() => {
		mocks.showError.mockReset()
	})

	it('shows the message', () => {
		handleError(axiosError(500), 'Unable to save')

		expect(mocks.showError).toHaveBeenCalledWith('Unable to save')
	})

	it('explains rate limiting', () => {
		handleError(axiosError(429), 'Unable to save')

		expect(mocks.showError).toHaveBeenCalledWith(expect.stringMatching(/^Unable to save\nThere were too many requests/))
	})

	it('handles errors that are no request errors', () => {
		handleError(new Error('Unexpected response'), '')

		expect(mocks.showError).toHaveBeenCalledWith('Error')
	})
})

describe('ocsData', () => {
	it('returns the body of a successful response', () => {
		const data = { ocs: { meta: { status: 'ok' }, data: [] } }

		expect(ocsData({ data })).toBe(data)
	})

	it('throws when the server did not report success', () => {
		expect(() => ocsData({ data: { ocs: { meta: { status: 'failure' } } } })).toThrow('Unexpected response')
	})
})
