/**
 * @copyright Copyright (c) 2022 John Molakvoæ <skjnldsv@protonmail.com>
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
import { generateUrl } from '@nextcloud/router'
import queryString from 'query-string'
import Router, { RawLocation, Route } from 'vue-router'
import Vue from 'vue'
import { ErrorHandler } from 'vue-router/types/router'

Vue.use(Router)

// Prevent router from throwing errors when we're already on the page we're trying to go to
const originalPush = Router.prototype.push as (to, onComplete?, onAbort?) => Promise<Route>
Router.prototype.push = function push(to: RawLocation, onComplete?: ((route: Route) => void) | undefined, onAbort?: ErrorHandler | undefined): Promise<Route> {
	if (onComplete || onAbort) return originalPush.call(this, to, onComplete, onAbort)
	return originalPush.call(this, to).catch(err => err)
}

const router = new Router({
	mode: 'history',

	// if index.php is in the url AND we got this far, then it's working:
	// let's keep using index.php in the url
	base: generateUrl('/apps/files'),
	linkActiveClass: 'active',

	routes: [
		{
			path: '/',
			// Pretending we're using the default view
			redirect: { name: 'filelist' },
		},
		{
			path: '/:view/:fileid?',
			name: 'filelist',
			props: true,
		},
	],

	// Custom stringifyQuery to prevent encoding of slashes in the url
	stringifyQuery(query) {
		const result = queryString.stringify(query).replace(/%2F/gmi, '/')
		return result ? ('?' + result) : ''
	},
})

export default router
