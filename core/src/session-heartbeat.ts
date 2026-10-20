/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'
import { emit } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import {
	fetchRequestToken,
	getRequestToken,
} from './OC/requesttoken.ts'
import { logger } from './utils/logger.ts'

interface OcJsConfig {
	auto_logout: boolean
	session_keepalive: boolean
	session_lifetime: number
}

// This is always set, exception would be e.g. error pages where this is undefined
const {
	auto_logout: autoLogout,
	session_keepalive: keepSessionAlive,
	session_lifetime: sessionLifetime,
} = loadState<Partial<OcJsConfig>>('core', 'config', {})

/**
 * Calls the server periodically to ensure that session and CSRF
 * token doesn't expire
 */
export function initSessionHeartBeat() {
	registerAutoLogout()

	if (!keepSessionAlive) {
		logger.info('Session heartbeat disabled')
		return
	}

	let interval = startPolling()
	window.addEventListener('online', async () => {
		logger.info('Browser is online again, resuming heartbeat')

		interval = startPolling()
		try {
			await poll()
			logger.info('Session token successfully updated after resuming network')

			// Let apps know we're online and requests will have the new token
			emit('networkOnline', {
				success: true,
			})
		} catch (error) {
			logger.error('could not update session token after resuming network', { error })

			// Let apps know we're online but requests might have an outdated token
			emit('networkOnline', {
				success: false,
			})
		}
	})

	window.addEventListener('offline', () => {
		logger.info('Browser is offline, stopping heartbeat')

		// Let apps know we're offline
		emit('networkOffline', {})

		clearInterval(interval)
		logger.info('Session heartbeat polling stopped')
	})
}

/**
 * Get interval in seconds
 */
function getInterval(): number {
	const interval = sessionLifetime
		? Math.floor(sessionLifetime / 2)
		: 900

	// minimum one minute, max 24 hours, default 15 minutes
	return Math.min(
		24 * 3600,
		Math.max(
			60,
			interval,
		),
	)
}

/**
 * Poll the CSRF token for changes.
 * This will also extend the current session if needed.
 */
async function poll() {
	try {
		await fetchRequestToken()
	} catch (error) {
		logger.error('session heartbeat failed', { error })
	}
}

/**
 * Start an window interval with the polling as the callback.
 *
 * @return The interval id
 */
function startPolling(): number {
	const interval = window.setInterval(poll, getInterval() * 1000)

	logger.info('session heartbeat polling started')
	return interval
}

/**
 * If enabled this will register event listeners to track if a user is active.
 * If not the user will be automatically logged out after the configured IDLE time.
 */
function registerAutoLogout() {
	if (!autoLogout || !getCurrentUser()) {
		return
	}

	const logoutCheckInterval = 1000
	let lastActive = Date.now()
	let lastStored = 0
	const markActive = () => {
		lastActive = Date.now()
		if (lastActive - lastStored < logoutCheckInterval) {
			return
		}
		lastStored = lastActive
		try {
			localStorage.setItem('lastActive', JSON.stringify(lastActive))
		} catch (error) {
			logger.debug('Could not share the last activity with other tabs', { error })
		}
	}

	// Capture phase, as editors may stop the propagation of input events
	window.addEventListener('mousemove', markActive, { capture: true })
	// Touch, pen and clicks, including clicks by assistive technology that do not move the mouse
	window.addEventListener('pointerdown', markActive, { capture: true })
	window.addEventListener('keydown', markActive, { capture: true })
	// Text insertion without key events, e.g. dictation
	window.addEventListener('beforeinput', markActive, { capture: true })
	// Wheel instead of scroll events, as scripts fire scroll events too
	window.addEventListener('wheel', markActive, { capture: true, passive: true })

	// Besides other tabs, apps with cross-origin editors (e.g. richdocuments) dispatch
	// this event in the same window to report activity inside their iframes
	window.addEventListener('storage', (event) => {
		if (event.key !== 'lastActive') {
			return
		}
		if (event.newValue === null) {
			return
		}
		lastActive = JSON.parse(event.newValue)
	})

	let intervalId = 0
	const logoutCheck = () => {
		const timeout = Date.now() - (sessionLifetime ?? 86400) * 1000
		if (lastActive < timeout) {
			clearTimeout(intervalId)
			logger.info('Inactivity timout reached, logging out')
			const logoutUrl = generateUrl('/logout') + '?requesttoken=' + encodeURIComponent(getRequestToken())
			window.location.href = logoutUrl
		}
	}
	intervalId = window.setInterval(logoutCheck, logoutCheckInterval)
}
