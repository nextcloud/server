/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { http, HttpResponse } from 'msw'
import { setupServer } from 'msw/node'
import { afterAll, afterEach, beforeAll, describe, expect, test } from 'vitest'
import { addServer, ApiError, deleteServer, TrustedServerStatus } from './api.ts'

export const handlers = [
	http.post('/ocs/v2.php/apps/federation/trusted-servers', async ({ request }) => {
		const { url } = (await request.json()) as { url: string }
		if (url === 'https://network-error.com') {
			return HttpResponse.error()
		}
		if (url === 'https://existing-server.com') {
			return HttpResponse.json({
				ocs: {
					meta: {
						status: 'failure',
						statuscode: 409,
						message: 'Server already exists',
					},
				},
			}, { status: 409 })
		}

		return HttpResponse.json({
			ocs: {
				meta: {
					status: 'ok',
				},
				data: {
					id: 1,
					url,
				},
			},
		})
	}),
	http.delete('/ocs/v2.php/apps/federation/trusted-servers/:id', async ({ params }) => {
		if (params.id === '1') {
			return HttpResponse.json({
				ocs: {
					meta: {
						status: 'ok',
					},
				},
			})
		}
		if (params.id === '2') {
			return HttpResponse.json({
				ocs: {
					meta: {
						status: 'failure',
						statuscode: 404,
						message: 'Server does not exist',
					},
				},
			}, { status: 404 })
		}

		return HttpResponse.error()
	}),
]

const server = setupServer(...handlers)
beforeAll(() => server.listen())
afterEach(() => server.resetHandlers())
afterAll(() => server.close())

describe('addServer', () => {
	test('returns a trusted server object on success', async () => {
		const server = await addServer('https://trusted-server.com')
		expect(server).toEqual({
			id: 1,
			url: 'https://trusted-server.com',
			status: TrustedServerStatus.STATUS_PENDING,
		})
	})

	test('throws API error when already added', async () => {
		await expect(() => addServer('https://existing-server.com')).rejects.toThrowError(ApiError)
		await expect(() => addServer('https://existing-server.com')).rejects.toThrow('Server already exists')
	})

	test('throws error when network error occurs', async () => {
		await expect(() => addServer('https://network-error.com')).rejects.toThrowError(Error)
		await expect(() => addServer('https://network-error.com')).rejects.not.toThrowError(ApiError)
	})
})

describe('deleteServer', () => {
	test('resolves on success', async () => {
		await expect(deleteServer(1)).resolves.not.toThrow()
	})

	test('throws API error when already added', async () => {
		await expect(() => deleteServer(2)).rejects.toThrowError(ApiError)
		await expect(() => deleteServer(2)).rejects.toThrow('Server does not exist')
	})

	test('throws error when network error occurs', async () => {
		await expect(() => deleteServer(3)).rejects.toThrowError(Error)
		await expect(() => deleteServer(3)).rejects.not.toThrowError(ApiError)
	})
})
