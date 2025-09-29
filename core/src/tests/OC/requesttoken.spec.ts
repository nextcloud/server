/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { setupServer } from 'msw/node'
import { http, HttpResponse } from 'msw'
import { beforeAll, beforeEach, describe, expect, it, vi } from 'vitest'
import { fetchRequestToken, getRequestToken, setRequestToken } from '../../OC/requesttoken.ts'

const eventbus = vi.hoisted(() => ({ emit: vi.fn() }))
vi.mock('@nextcloud/event-bus', () => eventbus)

const server = setupServer()

describe('getRequestToken', () => {
	it('can read the token from DOM', () => {
		mockToken('tokenmock-123')
		expect(getRequestToken()).toBe('tokenmock-123')
	})

	it('can handle missing token', () => {
		mockToken(undefined)
		expect(getRequestToken()).toBeUndefined()
	})
})

describe('setRequestToken', () => {
	beforeEach(() => {
		vi.resetAllMocks()
	})

	it('does emit an event on change', () => {
		setRequestToken('new-token')
		expect(eventbus.emit).toBeCalledTimes(1)
		expect(eventbus.emit).toBeCalledWith('csrf-token-update', { token: 'new-token' })
	})

	it('does set the new token to the DOM', () => {
		setRequestToken('new-token')
		expect(document.head.dataset.requesttoken).toBe('new-token')
	})

	it('does remember the new token', () => {
		mockToken('old-token')
		setRequestToken('new-token')
		expect(getRequestToken()).toBe('new-token')
	})

	it('throws if the token is not a string', () => {
		// @ts-expect-error mocking
		expect(() => setRequestToken(123)).toThrowError('Invalid CSRF token given')
	})

	it('throws if the token is not valid', () => {
		expect(() => setRequestToken('')).toThrowError('Invalid CSRF token given')
	})

	it('does not emit an event if the token is not valid', () => {
		expect(() => setRequestToken('')).toThrowError('Invalid CSRF token given')
		expect(eventbus.emit).not.toBeCalled()
	})
})

describe('fetchRequestToken', () => {
	const successfullCsrf = http.get('/index.php/csrftoken', () => {
		return HttpResponse.json({ token: 'new-token' })
	})
	const forbiddenCsrf = http.get('/index.php/csrftoken', () => {
		return HttpResponse.json([], { status: 403 })
	})
	const serverErrorCsrf = http.get('/index.php/csrftoken', () => {
		return HttpResponse.json([], { status: 500 })
	})
	const networkErrorCsrf = http.get('/index.php/csrftoken', () => {
		return new HttpResponse(null, { type: 'error' })
	})

	beforeAll(() => {
		server.listen()
	})

	beforeEach(() => {
		vi.resetAllMocks()
	})

	it('correctly parses response', async () => {
		server.use(successfullCsrf)

		mockToken('oldToken')
		const token = await fetchRequestToken()
		expect(token).toBe('new-token')
	})

	it('sets the token', async () => {
		server.use(successfullCsrf)

		mockToken('oldToken')
		await fetchRequestToken()
		expect(getRequestToken()).toBe('new-token')
	})

	it('does emit an event', async () => {
		server.use(successfullCsrf)

		await fetchRequestToken()
		expect(eventbus.emit).toHaveBeenCalledOnce()
		expect(eventbus.emit).toBeCalledWith('csrf-token-update', { token: 'new-token' })
	})

	it('handles 403 error due to invalid cookies', async () => {
		server.use(forbiddenCsrf)

		mockToken('oldToken')
		await expect(() => fetchRequestToken()).rejects.toThrowError('Could not fetch CSRF token from API')
		expect(getRequestToken()).toBe('oldToken')
	})

	it('handles server error', async () => {
		server.use(serverErrorCsrf)

		mockToken('oldToken')
		await expect(() => fetchRequestToken()).rejects.toThrowError('Could not fetch CSRF token from API')
		expect(getRequestToken()).toBe('oldToken')
	})

	it('handles network error', async () => {
		server.use(networkErrorCsrf)

		mockToken('oldToken')
		await expect(() => fetchRequestToken()).rejects.toThrow()
		expect(getRequestToken()).toBe('oldToken')
	})
})

/**
 * Mock the request token directly so we can test reading it.
 *
 * @param token - The CSRF token to mock
 */
function mockToken(token?: string) {
	if (token === undefined) {
		delete document.head.dataset.requesttoken
	} else {
		document.head.dataset.requesttoken = token
	}
}
