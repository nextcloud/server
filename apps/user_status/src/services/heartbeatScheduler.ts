/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getBuilder } from '@nextcloud/browser-storage'
import debounce from 'debounce'

const browserStorage = getBuilder('user_status').clearOnLogout().persist().build()

/** Has to stay below the server margin between `StatusService::REFRESH_STATUS_THRESHOLD` and `StatusService::INVALIDATE_STATUS_THRESHOLD`. */
export const HEARTBEAT_INTERVAL = 5 * 60 * 1000

export const AWAY_TIMEOUT = 2 * 60 * 1000

export const MOUSE_MOVE_DEBOUNCE = 2 * 1000

/** Below `HEARTBEAT_INTERVAL`, so a lone tab is never suppressed and its gap to the server never grows. */
export const HEARTBEAT_THROTTLE = 4 * 60 * 1000

/**
 * Send heartbeats on a fixed interval, and once more whenever the user comes back from being away.
 *
 * @param beat - Called with the current away state when a heartbeat is due
 * @return Function that stops the heartbeat and removes every timer and listener
 */
export function startHeartbeat(beat: (isAway: boolean) => void): () => void {
	let isAway = false
	let awayTimeout: ReturnType<typeof setTimeout> | undefined
	let onVisible: (() => void) | undefined

	const announce = (force = false) => {
		// NaN (missing or unparseable) and a negative age (future timestamp)
		// both fail this test, so both send
		const age = Date.now() - Number.parseInt(browserStorage.getItem('lastHeartbeat') ?? '', 10)
		if (!force && age >= 0 && age < HEARTBEAT_THROTTLE) {
			return
		}
		browserStorage.setItem('lastHeartbeat', String(Date.now()))
		beat(isAway)
	}

	const onMouseMove = debounce(() => {
		const wasAway = isAway
		isAway = false

		clearTimeout(awayTimeout)
		awayTimeout = setTimeout(() => {
			isAway = true
		}, AWAY_TIMEOUT)

		if (wasAway) {
			// Coming back is real signal, so it is never throttled
			announce(true)
		}
	}, MOUSE_MOVE_DEBOUNCE, { immediate: true })

	const interval = setInterval(() => announce(), HEARTBEAT_INTERVAL)
	window.addEventListener('mousemove', onMouseMove, {
		capture: true,
		passive: true,
	})

	if (document.visibilityState === 'hidden') {
		// A tab opened in the background has nothing to report until it is looked at
		onVisible = () => {
			if (document.visibilityState === 'hidden') {
				return
			}
			document.removeEventListener('visibilitychange', onVisible!)
			announce()
		}
		document.addEventListener('visibilitychange', onVisible)
	} else {
		announce()
	}

	return () => {
		clearInterval(interval)
		clearTimeout(awayTimeout)
		onMouseMove.clear()
		window.removeEventListener('mousemove', onMouseMove, { capture: true })
		if (onVisible) {
			document.removeEventListener('visibilitychange', onVisible)
		}
	}
}
