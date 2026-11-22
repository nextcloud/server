/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { NextcloudUser } from '@nextcloud/auth'
import type { MockInstance } from 'vitest'

import { afterEach, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest'

const requestToken = vi.hoisted(() => ({
	fetchRequestToken: vi.fn<() => Promise<string>>(),
	getRequestToken: vi.fn<() => string>(),
	setRequestToken: vi.fn<(token: string) => void>(),
}))
vi.mock('../../OC/requesttoken.ts', () => requestToken)

const initialState = vi.hoisted(() => ({ loadState: vi.fn() }))
vi.mock('@nextcloud/initial-state', () => initialState)

const auth = vi.hoisted(() => ({ getCurrentUser: vi.fn<() => NextcloudUser | null>(() => null) }))
vi.mock('@nextcloud/auth', () => auth)

describe('Session heartbeat', () => {
	let addEventListener: MockInstance<typeof window.addEventListener>

	beforeAll(() => {
		vi.useFakeTimers()
	})

	beforeEach(() => {
		vi.clearAllTimers()
		vi.resetModules()
		vi.resetAllMocks()
		addEventListener = vi.spyOn(window, 'addEventListener')
	})

	afterEach(() => {
		// Each test imports a new module instance, so its listeners must not outlive the test
		for (const listener of addEventListener.mock.calls) {
			window.removeEventListener(...listener)
		}
		vi.restoreAllMocks()
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

	describe('auto logout', () => {
		const pageUrl = 'http://nextcloud.local/apps/text'
		const logoutUrl = '/index.php/logout?requesttoken=csrf-token'
		let originalLocation: Location

		beforeEach(() => {
			originalLocation = window.location
			Object.defineProperty(window, 'location', {
				configurable: true,
				writable: true,
				value: { href: pageUrl },
			})

			auth.getCurrentUser.mockReturnValue({ uid: 'user', displayName: 'User', isAdmin: false })
			requestToken.getRequestToken.mockReturnValue('csrf-token')
			initialState.loadState.mockImplementationOnce(() => ({
				auto_logout: true,
				session_lifetime: 120,
			}))
		})

		afterEach(() => {
			Object.defineProperty(window, 'location', {
				configurable: true,
				writable: true,
				value: originalLocation,
			})
			document.body.replaceChildren()
			localStorage.clear()
		})

		/**
		 * Dispatch the activity before the session lifetime is over and
		 * expect the logout to happen one session lifetime after it.
		 *
		 * @param dispatchActivity - Callback dispatching the user activity
		 */
		async function expectActivity(dispatchActivity: () => void) {
			const { initSessionHeartBeat } = await import('../../session-heartbeat.ts')
			initSessionHeartBeat()

			await vi.advanceTimersByTimeAsync(100 * 1000)
			dispatchActivity()

			await vi.advanceTimersByTimeAsync(120 * 1000)
			expect(window.location.href).toBe(pageUrl)

			await vi.advanceTimersByTimeAsync(1000)
			expect(window.location.href).toBe(logoutUrl)
		}

		it('logs out once the session lifetime passed without activity', async () => {
			const { initSessionHeartBeat } = await import('../../session-heartbeat.ts')
			initSessionHeartBeat()

			await vi.advanceTimersByTimeAsync(120 * 1000)
			expect(window.location.href).toBe(pageUrl)

			await vi.advanceTimersByTimeAsync(1000)
			expect(window.location.href).toBe(logoutUrl)
		})

		it.each([
			{ activity: 'mouse movement', createEvent: () => new MouseEvent('mousemove', { bubbles: true }) },
			{ activity: 'touch', createEvent: () => new PointerEvent('pointerdown', { bubbles: true, pointerType: 'touch' }) },
			{ activity: 'click without mouse movement', createEvent: () => new PointerEvent('pointerdown', { bubbles: true, pointerType: 'mouse' }) },
			{ activity: 'key press', createEvent: () => new KeyboardEvent('keydown', { bubbles: true }) },
			{ activity: 'text input', createEvent: () => new InputEvent('beforeinput', { bubbles: true, inputType: 'insertText', data: 'a' }) },
			{ activity: 'wheel scrolling', createEvent: () => new WheelEvent('wheel', { bubbles: true }) },
		])('counts $activity as activity, even when an editor stops its propagation', async ({ createEvent }) => {
			const activityEvent = createEvent()
			const editor = document.createElement('div')
			editor.addEventListener(activityEvent.type, (event) => event.stopPropagation())
			document.body.appendChild(editor)

			await expectActivity(() => editor.dispatchEvent(activityEvent))
		})

		it('does not delay scrolling with its wheel listener', async () => {
			const { initSessionHeartBeat } = await import('../../session-heartbeat.ts')
			initSessionHeartBeat()

			expect(addEventListener).toHaveBeenCalledWith('wheel', expect.any(Function), { capture: true, passive: true })
		})

		it('counts activity reported by apps through a storage event in the same window', async () => {
			await expectActivity(() => window.dispatchEvent(new StorageEvent('storage', {
				key: 'lastActive',
				newValue: Date.now().toString(),
			})))
		})

		it('does not count scrolling done by scripts as activity', async () => {
			const element = document.createElement('div')
			document.body.appendChild(element)

			const { initSessionHeartBeat } = await import('../../session-heartbeat.ts')
			initSessionHeartBeat()

			await vi.advanceTimersByTimeAsync(100 * 1000)
			element.dispatchEvent(new Event('scroll'))

			await vi.advanceTimersByTimeAsync(21 * 1000)
			expect(window.location.href).toBe(logoutUrl)
		})

		it('stores the last activity at most once per second', async () => {
			const start = Date.now()
			const { initSessionHeartBeat } = await import('../../session-heartbeat.ts')
			initSessionHeartBeat()

			await vi.advanceTimersByTimeAsync(100 * 1000)
			document.body.dispatchEvent(new MouseEvent('mousemove', { bubbles: true }))
			expect(localStorage.getItem('lastActive')).toBe(JSON.stringify(start + 100 * 1000))

			await vi.advanceTimersByTimeAsync(999)
			document.body.dispatchEvent(new MouseEvent('mousemove', { bubbles: true }))
			expect(localStorage.getItem('lastActive')).toBe(JSON.stringify(start + 100 * 1000))

			await vi.advanceTimersByTimeAsync(1)
			document.body.dispatchEvent(new MouseEvent('mousemove', { bubbles: true }))
			expect(localStorage.getItem('lastActive')).toBe(JSON.stringify(start + 101 * 1000))
		})

		it('stores the first activity right after the page load', async () => {
			const start = Date.now()
			const { initSessionHeartBeat } = await import('../../session-heartbeat.ts')
			initSessionHeartBeat()

			await vi.advanceTimersByTimeAsync(500)
			document.body.dispatchEvent(new MouseEvent('mousemove', { bubbles: true }))
			expect(localStorage.getItem('lastActive')).toBe(JSON.stringify(start + 500))
		})

		it('counts activity that is not stored for other tabs yet', async () => {
			const { initSessionHeartBeat } = await import('../../session-heartbeat.ts')
			initSessionHeartBeat()

			await vi.advanceTimersByTimeAsync(99500)
			document.body.dispatchEvent(new MouseEvent('mousemove', { bubbles: true }))
			await vi.advanceTimersByTimeAsync(999)
			document.body.dispatchEvent(new MouseEvent('mousemove', { bubbles: true }))

			// The logout for the stored activity would be due at 220 s, the one for the latest activity at 221 s
			await vi.advanceTimersByTimeAsync(220000 - 100499)
			expect(window.location.href).toBe(pageUrl)

			await vi.advanceTimersByTimeAsync(1000)
			expect(window.location.href).toBe(logoutUrl)
		})

		it('counts activity when the browser storage is not available', async () => {
			vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
				throw new DOMException('The quota has been exceeded.', 'QuotaExceededError')
			})
			const onError = vi.fn()
			window.addEventListener('error', onError)

			await expectActivity(() => document.body.dispatchEvent(new MouseEvent('mousemove', { bubbles: true })))
			expect(onError).not.toHaveBeenCalled()
		})
	})
})
