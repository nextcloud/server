/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { AxiosError } from 'axios'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const { showError } = vi.hoisted(() => ({
	showError: vi.fn(),
}))

vi.mock('@nextcloud/dialogs', () => ({
	showError,
}))

vi.mock(import('@nextcloud/event-bus'), async (importOriginal) => {
	const actual = await importOriginal()
	return {
		...actual,
		emit: vi.fn(),
	}
})

vi.mock('@nextcloud/router', () => ({
	generateOcsUrl: vi.fn().mockReturnValue('/ocs/v2.php/apps/files_sharing/api/v1/shares'),
}))

vi.mock('../models/Share.ts', () => ({
	default: vi.fn().mockImplementation(function(data) {
		Object.assign(this, data)
		this.id = data?.id ?? 1
	}),
}))

vi.mock('../services/logger.ts', () => ({
	default: { error: vi.fn(), debug: vi.fn(), info: vi.fn() },
}))

import axios from '@nextcloud/axios'
import ShareRequests from './ShareRequests.js'

describe('ShareRequests mixin', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	describe('createShare', () => {
		it('creates a share successfully', async () => {
			const shareData = { id: 123, path: '/test.txt' }
			vi.spyOn(axios, 'post').mockResolvedValueOnce({
				data: { ocs: { data: shareData } },
			} as never)

			const result = await ShareRequests.methods.createShare({
				path: '/test.txt',
				shareType: 3,
			})

			expect(result).toMatchObject(shareData)
			expect(showError).not.toHaveBeenCalled()
		})

		it('shows a dedicated rate limit message on HTTP 429 response', async () => {
			const axiosError = new AxiosError('Too Many Requests')
			axiosError.response = {
				status: 429,
				statusText: 'Too Many Requests',
				headers: {},
				config: {} as never,
				data: {},
			}
			vi.spyOn(axios, 'post').mockRejectedValueOnce(axiosError)

			await expect(ShareRequests.methods.createShare({
				path: '/test.txt',
				shareType: 3,
			})).rejects.toThrow('Share creation is temporarily rate limited. Please wait a few minutes before creating more shares.')

			expect(showError).toHaveBeenCalledWith('Share creation is temporarily rate limited. Please wait a few minutes before creating more shares.')
		})

		it('shows the backend error message if provided on non-429 failure', async () => {
			const axiosError = new AxiosError('Bad Request')
			axiosError.response = {
				status: 400,
				statusText: 'Bad Request',
				headers: {},
				config: {} as never,
				data: {
					ocs: {
						meta: {
							message: 'Custom backend validation failed',
						},
					},
				},
			}
			vi.spyOn(axios, 'post').mockRejectedValueOnce(axiosError)

			await expect(ShareRequests.methods.createShare({
				path: '/test.txt',
				shareType: 3,
			})).rejects.toThrow('Custom backend validation failed')

			expect(showError).toHaveBeenCalledWith('Custom backend validation failed')
		})

		it('falls back to default error message when no specific message is available', async () => {
			const genericError = new Error('Network failure')
			vi.spyOn(axios, 'post').mockRejectedValueOnce(genericError)

			await expect(ShareRequests.methods.createShare({
				path: '/test.txt',
				shareType: 3,
			})).rejects.toThrow('Error creating the share')

			expect(showError).toHaveBeenCalledWith('Error creating the share')
		})
	})
})
