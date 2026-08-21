/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import debounce from 'debounce'

/** Has to stay below the server margin between `StatusService::REFRESH_STATUS_THRESHOLD` and `StatusService::INVALIDATE_STATUS_THRESHOLD`. */
export const HEARTBEAT_INTERVAL = 5 * 60 * 1000

export const AWAY_TIMEOUT = 2 * 60 * 1000

export const MOUSE_MOVE_DEBOUNCE = 2 * 1000

/**
 * Send heartbeats on a fixed interval, and once more whenever the user comes back from being away.
 *
 * @param beat - Called with the current away state when a heartbeat is due
 * @return Function that stops the heartbeat and removes every timer and listener
 */
export function startHeartbeat(beat: (isAway: boolean) => void): () => void {
	let isAway = false
	let awayTimeout: ReturnType<typeof setTimeout> | undefined

	const onMouseMove = debounce(() => {
		const wasAway = isAway
		isAway = false

		clearTimeout(awayTimeout)
		awayTimeout = setTimeout(() => {
			isAway = true
		}, AWAY_TIMEOUT)

		if (wasAway) {
			beat(isAway)
		}
	}, MOUSE_MOVE_DEBOUNCE, { immediate: true })

	const interval = setInterval(() => beat(isAway), HEARTBEAT_INTERVAL)
	window.addEventListener('mousemove', onMouseMove, {
		capture: true,
		passive: true,
	})

	beat(isAway)

	return () => {
		clearInterval(interval)
		clearTimeout(awayTimeout)
		onMouseMove.clear()
		window.removeEventListener('mousemove', onMouseMove, { capture: true })
	}
}
