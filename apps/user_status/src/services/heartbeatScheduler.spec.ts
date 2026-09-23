/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getBuilder } from '@nextcloud/browser-storage'
import { afterEach, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest'
import {
	AWAY_TIMEOUT,
	HEARTBEAT_INTERVAL,
	HEARTBEAT_THROTTLE,
	MOUSE_MOVE_DEBOUNCE,
	startHeartbeat,
} from './heartbeatScheduler.ts'

const HOUR = 60 * 60 * 1000

// The same scoped store the scheduler writes to, so seeding uses the real key
const storage = getBuilder('user_status').clearOnLogout().persist().build()

let stop: (() => void) | undefined

/**
 * Move the mouse for a second, then hold still.
 *
 * @param gap - Milliseconds of stillness after the burst
 */
async function moveThenRest(gap: number): Promise<void> {
	for (let i = 0; i < 10; i++) {
		window.dispatchEvent(new MouseEvent('mousemove'))
		await vi.advanceTimersByTimeAsync(100)
	}
	await vi.advanceTimersByTimeAsync(gap)
}

describe('heartbeat scheduler', () => {
	beforeAll(() => {
		// `debounce` compares Date.now() against its own timestamp, so Date has to stay faked alongside the timers
		vi.useFakeTimers()
	})

	beforeEach(() => {
		vi.clearAllTimers()
		vi.resetAllMocks()
		localStorage.clear()
	})

	afterEach(() => {
		stop?.()
		stop = undefined
	})

	it('sends a heartbeat on start', () => {
		const beat = vi.fn()
		stop = startHeartbeat(beat)

		expect(beat).toHaveBeenCalledTimes(1)
		expect(beat).toHaveBeenCalledWith(false)
	})

	it('sends a heartbeat every five minutes', async () => {
		const beat = vi.fn()
		stop = startHeartbeat(beat)

		await vi.advanceTimersByTimeAsync(HEARTBEAT_INTERVAL - 1000)
		expect(beat).toHaveBeenCalledTimes(1)

		await vi.advanceTimersByTimeAsync(1000)
		expect(beat).toHaveBeenCalledTimes(2)

		await vi.advanceTimersByTimeAsync(HEARTBEAT_INTERVAL)
		expect(beat).toHaveBeenCalledTimes(3)
	})

	it('does not send extra heartbeats while the user keeps moving the mouse', async () => {
		const beat = vi.fn()
		stop = startHeartbeat(beat)

		const cycle = 1000 + 5000
		for (let elapsed = 0; elapsed < HOUR; elapsed += cycle) {
			await moveThenRest(5000)
		}

		expect(beat).toHaveBeenCalledTimes(1 + HOUR / HEARTBEAT_INTERVAL)
		// the away countdown is restarted, never accumulated
		expect(vi.getTimerCount()).toBeLessThanOrEqual(3)
	})

	it('reports the user as away after two minutes without mouse movement', async () => {
		const beat = vi.fn()
		stop = startHeartbeat(beat)

		window.dispatchEvent(new MouseEvent('mousemove'))
		await vi.advanceTimersByTimeAsync(AWAY_TIMEOUT + 1000)

		await vi.advanceTimersByTimeAsync(HEARTBEAT_INTERVAL - AWAY_TIMEOUT - 1000)
		expect(beat).toHaveBeenLastCalledWith(true)
	})

	it('sends one heartbeat when the user comes back from being away', async () => {
		const beat = vi.fn()
		stop = startHeartbeat(beat)

		window.dispatchEvent(new MouseEvent('mousemove'))
		await vi.advanceTimersByTimeAsync(AWAY_TIMEOUT + MOUSE_MOVE_DEBOUNCE)
		const beforeReturn = beat.mock.calls.length

		window.dispatchEvent(new MouseEvent('mousemove'))
		expect(beat).toHaveBeenCalledTimes(beforeReturn + 1)
		expect(beat).toHaveBeenLastCalledWith(false)
	})

	it('stops the interval, the away countdown and the mouse listener', async () => {
		const beat = vi.fn()
		const stopHeartbeat = startHeartbeat(beat)
		window.dispatchEvent(new MouseEvent('mousemove'))

		stopHeartbeat()

		await vi.advanceTimersByTimeAsync(3 * HEARTBEAT_INTERVAL)
		window.dispatchEvent(new MouseEvent('mousemove'))
		await vi.advanceTimersByTimeAsync(3 * HEARTBEAT_INTERVAL)

		expect(beat).toHaveBeenCalledTimes(1)
		expect(vi.getTimerCount()).toBe(0)
	})

	it('skips the start heartbeat when another tab reported recently', () => {
		storage.setItem('lastHeartbeat', String(Date.now() - HEARTBEAT_THROTTLE + 1000))
		const beat = vi.fn()
		stop = startHeartbeat(beat)

		expect(beat).not.toHaveBeenCalled()
	})

	it('sends the start heartbeat once the throttle window has passed', () => {
		storage.setItem('lastHeartbeat', String(Date.now() - HEARTBEAT_THROTTLE - 1000))
		const beat = vi.fn()
		stop = startHeartbeat(beat)

		expect(beat).toHaveBeenCalledTimes(1)
	})

	it.each([
		['a timestamp from the future', () => String(Date.now() + HOUR)],
		['an unparseable timestamp', () => 'not a number'],
	])('sends the start heartbeat despite %s', (_label, stored) => {
		storage.setItem('lastHeartbeat', stored())
		const beat = vi.fn()
		stop = startHeartbeat(beat)

		expect(beat).toHaveBeenCalledTimes(1)
	})

	it('sends one heartbeat in total for two schedulers on the same page', () => {
		const first = vi.fn()
		const second = vi.fn()
		stop = startHeartbeat(first)
		const stopSecond = startHeartbeat(second)

		expect(first).toHaveBeenCalledTimes(1)
		expect(second).not.toHaveBeenCalled()
		stopSecond()
	})

	it('never throttles the user coming back from away', async () => {
		const beat = vi.fn()
		stop = startHeartbeat(beat)

		window.dispatchEvent(new MouseEvent('mousemove'))
		await vi.advanceTimersByTimeAsync(AWAY_TIMEOUT + MOUSE_MOVE_DEBOUNCE)
		beat.mockClear()

		// Well inside the throttle window
		window.dispatchEvent(new MouseEvent('mousemove'))

		expect(beat).toHaveBeenCalledTimes(1)
	})

	it('waits for the first reveal before announcing a background tab', () => {
		const visibility = vi.spyOn(document, 'visibilityState', 'get').mockReturnValue('hidden')
		const beat = vi.fn()
		stop = startHeartbeat(beat)

		expect(beat).not.toHaveBeenCalled()

		visibility.mockReturnValue('visible')
		document.dispatchEvent(new Event('visibilitychange'))

		expect(beat).toHaveBeenCalledTimes(1)
		visibility.mockRestore()
	})
})
