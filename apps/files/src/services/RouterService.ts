/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Location, Route } from 'vue-router'
import type VueRouter from 'vue-router'

import { unref } from 'vue'

export default class RouterService {
	// typescript compiles this to `#router` to make it private even in JS,
	// but in TS it needs to be called without the visibility specifier
	private router: VueRouter

	/**
	 * The location of a navigation that was requested but is not applied yet.
	 *
	 * vue-router applies navigations asynchronously, so `currentRoute` still
	 * holds the previous route until the navigation resolves. Several stores
	 * react to the same state change and each merges its own change into the
	 * route it reads here, so without this they would all read the route from
	 * before the first navigation and overwrite each other's parameters.
	 */
	private pendingLocation?: Location

	constructor(router: VueRouter) {
		this.router = router
	}

	/**
	 * The route the wrapped router is currently on.
	 *
	 * This service is shared between the files app (vue-router 5, where
	 * `currentRoute` is a ref) and the public share bundle (vue-router 3, where
	 * it is the route itself), so the value has to be unwrapped.
	 */
	private get route(): Route {
		return unref(this.router.currentRoute) as Route
	}

	get name(): string | null | undefined {
		return this.pendingLocation?.name ?? this.route.name
	}

	get query(): Record<string, string | (string | null)[] | null | undefined> {
		return this.pendingLocation?.query ?? this.route.query ?? {}
	}

	get params(): Record<string, string> {
		return (this.pendingLocation?.params ?? this.route.params ?? {}) as Record<string, string>
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
		this.pendingLocation = undefined
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
		name ??= this.name as string
		const location: Location = { name, query, params }
		this.pendingLocation = location

		const navigation = replace
			? this._router.replace(location)
			: this._router.push(location)

		return navigation.finally(() => {
			// only the newest request may clear it, an older one is already obsolete
			if (this.pendingLocation === location) {
				this.pendingLocation = undefined
			}
		})
	}
}
