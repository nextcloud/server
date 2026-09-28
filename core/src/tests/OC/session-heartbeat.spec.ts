/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeAll, beforeEach, describe, expect, it, vi } from 'vitest'

const requestToken = vi.hoisted(() => ({
	fetchRequestToken: vi.fn<() => Promise<string>>(),
	setRequestToken: vi.fn<(token: string) => void>(),
}))
vi.mock('../../OC/requesttoken.ts', () => requestToken)

const initialState = vi.hoisted(() => ({ loadState: vi.fn() }))
vi.mock('@nextcloud/initial-state', () => initialState)

describe('Session heartbeat', () => {
	beforeAll(() => {
		vi.useFakeTimers()
	})

	beforeEach(() => {
		vi.clearAllTimers()
		vi.resetModules()
		vi.resetAllMocks()
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
		await vi.advanceTimersByTimeAsync(100 * 1000)
		expect(requestToken.fetchRequestToken).not.toBeCalled()

		// reach past half, one call
		await vi.advanceTimersByTimeAsync(60 * 1000)
		expect(requestToken.fetchRequestToken).toBeCalledTimes(1)

		// almost there to the next, still one
		await vi.advanceTimersByTimeAsync(135 * 1000)
		expect(requestToken.fetchRequestToken).toBeCalledTimes(1)

		// past it, second call
		await vi.advanceTimersByTimeAsync(5 * 1000)
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
		await vi.advanceTimersByTimeAsync(100 * 1000)
		expect(requestToken.fetchRequestToken).not.toBeCalled()

		// more than one, still nothing
		await vi.advanceTimersByTimeAsync(300 * 1000)
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
		await vi.advanceTimersByTimeAsync(30 * 1000)
		expect(requestToken.fetchRequestToken).not.toBeCalled()

		// 59 / 55 seconds should not be called except it does not limit
		await vi.advanceTimersByTimeAsync(29 * 1000)
		expect(requestToken.fetchRequestToken).not.toBeCalled()

		// now one minute has passed
		await vi.advanceTimersByTimeAsync(1000)
		expect(requestToken.fetchRequestToken).toHaveBeenCalledOnce()
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
		await vi.advanceTimersByTimeAsync(23 * 60 * 60 * 1000)
		expect(requestToken.fetchRequestToken).not.toBeCalled()

		// one day - it should be called now
		await vi.advanceTimersByTimeAsync(60 * 60 * 1000)
		expect(requestToken.fetchRequestToken).toHaveBeenCalledOnce()
	})
})
