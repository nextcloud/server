/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Route } from 'vue-router'
import type VueRouter from 'vue-router'
import type { Dictionary, Location } from 'vue-router/types/router'

export default class RouterService {

	private _router: VueRouter

	constructor(router: VueRouter) {
		this._router = router
	}

	get name(): string | null | undefined {
		return this._router.currentRoute.name
	}

	get query(): Dictionary<string | (string | null)[] | null | undefined> {
		return this._router.currentRoute.query || {}
	}

	get params(): Dictionary<string> {
		return this._router.currentRoute.params || {}
	}

	/**
	 * This is a protected getter only for internal use
	 * @private
	 */
	get router() {
		return this._router
	}

	/**
	 * Trigger a route change on the files app
	 *
	 * @param path the url path, eg: '/trashbin?dir=/Deleted'
	 * @param replace replace the current history
	 * @see https://router.vuejs.org/guide/essentials/navigation.html#navigate-to-a-different-location
	 */
	goTo(path: string, replace = false): Promise<Route> {
		return this._router.push({
			path,
			replace,
		})
	}

	/**
	 * Trigger a route change on the files App
	 *
	 * @param name the route name
	 * @param params the route parameters
	 * @param query the url query parameters
	 * @param replace replace the current history
	 * @see https://router.vuejs.org/guide/essentials/navigation.html#navigate-to-a-different-location
	 */
	goToRoute(
		name?: string,
		params?: Dictionary<string>,
		query?: Dictionary<string | (string | null)[] | null | undefined>,
		replace?: boolean,
	): Promise<Route> {
		return this._router.push({
			name,
			query,
			params,
			replace,
		} as Location)
	}

}
