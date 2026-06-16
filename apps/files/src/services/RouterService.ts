/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Location, Route } from 'vue-router'
import type VueRouter from 'vue-router'

export default class RouterService {
	// typescript compiles this to `#router` to make it private even in JS,
	// but in TS it needs to be called without the visibility specifier
	private router: VueRouter

	constructor(router: VueRouter) {
		this.router = router
	}

	get name(): string | null | undefined {
		return this.router.currentRoute.name
	}

	get query(): Record<string, string | (string | null)[] | null | undefined> {
		return this.router.currentRoute.query || {}
	}

	get params(): Record<string, string> {
		return this.router.currentRoute.params || {}
	}

	/**
	 * This is a protected getter only for internal use
	 *
	 */
	get _router() {
		return this.router
	}

	/**
	 * Trigger a route change on the files app
	 *
	 * @param path the url path, eg: '/trashbin?dir=/Deleted'
	 * @param replace replace the current history
	 * @see https://router.vuejs.org/guide/essentials/navigation.html#navigate-to-a-different-location
	 */
	goTo(path: string, replace = false): Promise<Route> {
		return this.router.push({
			path,
			replace,
		})
	}

	/**
	 * Trigger a route change on the files App
	 *
	 * @param name - The route name or null to keep current route and just update params/query
	 * @param params the route parameters
	 * @param query the url query parameters
	 * @param replace replace the current history
	 * @see https://router.vuejs.org/guide/essentials/navigation.html#navigate-to-a-different-location
	 */
	goToRoute(
		name: string | null,
		params: Record<string, string>,
		query?: Record<string, string | (string | null)[] | null | undefined>,
		replace?: boolean,
	): Promise<Route> {
		name ??= this.router.currentRoute.name as string
		const location: Location = { name, query, params }
		if (replace) {
			return this._router.replace(location)
		}
		return this._router.push(location)
	}
}
