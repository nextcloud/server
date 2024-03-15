/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
