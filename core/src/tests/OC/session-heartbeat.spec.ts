/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeAll, beforeEach, describe, expect, it } from '@jest/globals'

// eslint-disable-next-line no-var
var requestToken = {
	fetchRequestToken: jest.fn<Promise<string>, []>(),
	setRequestToken: jest.fn<void, [string]>(),
}
jest.mock('../../OC/requesttoken.ts', () => requestToken)

// eslint-disable-next-line no-var
var initialState = { loadState: jest.fn() }
jest.mock('@nextcloud/initial-state', () => initialState)

describe('Session heartbeat', () => {
	beforeAll(() => {
		jest.useFakeTimers()
	})

	beforeEach(() => {
		jest.clearAllTimers()
		jest.resetModules()
		jest.resetAllMocks()
	})

	it('sends heartbeat half the session lifetime when heartbeat enabled', async () => {
		initialState.loadState.mockImplementationOnce(() => ({
			session_keepalive: true,
			session_lifetime: 300,
		}))

		const { initSessionHeartBeat } = await import('../../session-heartbeat.ts')
		initSessionHeartBeat()

		// initial state loaded
		expect(initialState.loadState).toBeCalledWith('core', 'config', {})

		// less than half, still nothing
		await jest.advanceTimersByTimeAsync(100 * 1000)
		expect(requestToken.fetchRequestToken).not.toBeCalled()

		// reach past half, one call
		await jest.advanceTimersByTimeAsync(60 * 1000)
		expect(requestToken.fetchRequestToken).toBeCalledTimes(1)

		// almost there to the next, still one
		await jest.advanceTimersByTimeAsync(135 * 1000)
		expect(requestToken.fetchRequestToken).toBeCalledTimes(1)

		// past it, second call
		await jest.advanceTimersByTimeAsync(5 * 1000)
		expect(requestToken.fetchRequestToken).toBeCalledTimes(2)
	})

	it('does not send heartbeat when heartbeat disabled', async () => {
		initialState.loadState.mockImplementationOnce(() => ({
			session_keepalive: false,
			session_lifetime: 300,
		}))

		const { initSessionHeartBeat } = await import('../../session-heartbeat.ts')
		initSessionHeartBeat()

		// initial state loaded
		expect(initialState.loadState).toBeCalledWith('core', 'config', {})

		// less than half, still nothing
		await jest.advanceTimersByTimeAsync(100 * 1000)
		expect(requestToken.fetchRequestToken).not.toBeCalled()

		// more than one, still nothing
		await jest.advanceTimersByTimeAsync(300 * 1000)
		expect(requestToken.fetchRequestToken).not.toBeCalled()
	})

	it('limit heartbeat to at least one minute', async () => {
		initialState.loadState.mockImplementationOnce(() => ({
			session_keepalive: true,
			session_lifetime: 55,
		}))

		const { initSessionHeartBeat } = await import('../../session-heartbeat.ts')
		initSessionHeartBeat()

		// initial state loaded
		expect(initialState.loadState).toBeCalledWith('core', 'config', {})

		// 30 / 55 seconds
		await jest.advanceTimersByTimeAsync(30 * 1000)
		expect(requestToken.fetchRequestToken).not.toBeCalled()

		// 59 / 55 seconds should not be called except it does not limit
		await jest.advanceTimersByTimeAsync(29 * 1000)
		expect(requestToken.fetchRequestToken).not.toBeCalled()

		// now one minute has passed
		await jest.advanceTimersByTimeAsync(1000)
		expect(requestToken.fetchRequestToken).toBeCalledTimes(1)
	})

	it('limit heartbeat to at least one minute', async () => {
		initialState.loadState.mockImplementationOnce(() => ({
			session_keepalive: true,
			session_lifetime: 50 * 60 * 60,
		}))

		const { initSessionHeartBeat } = await import('../../session-heartbeat.ts')
		initSessionHeartBeat()

		// initial state loaded
		expect(initialState.loadState).toBeCalledWith('core', 'config', {})

		// 23 hours
		await jest.advanceTimersByTimeAsync(23 * 60 * 60 * 1000)
		expect(requestToken.fetchRequestToken).not.toBeCalled()

		// one day - it should be called now
		await jest.advanceTimersByTimeAsync(60 * 60 * 1000)
		expect(requestToken.fetchRequestToken).toBeCalledTimes(1)
	})
})
