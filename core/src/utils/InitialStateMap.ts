/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Initial states of all apps as rendered by the server, grouped by app and key.
 */
export type InitialStates = Record<string, Record<string, unknown>>

/**
 * Provides the initial states rendered by the server as `window._nc_initial_state`.
 *
 * The server renders all initial states as one JSON document in `<script type="application/json" id="initial-state">`,
 * which is parsed on first access.
 *
 * `@nextcloud/initial-state` 3.x looks up and caches values in `window._nc_initial_state`
 * using `#initial-state-<app>-<key>` as key, so the map is also indexed by those keys.
 */
export class InitialStateMap extends Map<string, unknown> {
	#states?: InitialStates

	/**
	 * Check whether an initial state was provided.
	 *
	 * @param app - The app id
	 * @param key - The initial state key
	 */
	hasState(app: string, key: string): boolean {
		const states = this.#load()
		return Object.hasOwn(states, app) && Object.hasOwn(states[app]!, key)
	}

	/**
	 * Get an initial state.
	 *
	 * @param app - The app id
	 * @param key - The initial state key
	 * @return The value or undefined if there is no such initial state
	 */
	getState(app: string, key: string): unknown {
		return this.hasState(app, key) ? this.#states![app]![key] : undefined
	}

	override has(key: string): boolean {
		this.#load()
		return super.has(key)
	}

	override get(key: string): unknown {
		this.#load()
		return super.get(key)
	}

	/**
	 * Parse the initial states on first use and index them by their legacy keys.
	 */
	#load(): InitialStates {
		if (this.#states !== undefined) {
			return this.#states
		}

		this.#states = {}
		const element = document.getElementById('initial-state')
		if (element === null) {
			return this.#states
		}

		try {
			this.#states = JSON.parse(element.textContent!) as InitialStates
		} catch (error) {
			reportError(new Error('Could not parse the initial state', { cause: error }))
			return this.#states
		}

		for (const [app, states] of Object.entries(this.#states)) {
			for (const [key, value] of Object.entries(states)) {
				const legacyKey = `#initial-state-${app}-${key}`
				// values cached before the first access take precedence
				if (!super.has(legacyKey)) {
					super.set(legacyKey, value)
				}
			}
		}
		return this.#states
	}
}
