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
import type { RawLocation, Route } from 'vue-router'
import { generateUrl } from '@nextcloud/router'
import queryString from 'query-string'
import Router, { isNavigationFailure, NavigationFailureType } from 'vue-router'
import Vue from 'vue'
import logger from '../logger'

Vue.use(Router)

// Prevent router from throwing errors when we're already on the page we're trying to go to
const originalPush = Router.prototype.push
Router.prototype.push = (function(this: Router, ...args: Parameters<typeof originalPush>) {
	if (args.length > 1) {
		return originalPush.call(this, ...args)
	}
	return originalPush.call<Router, [RawLocation], Promise<Route>>(this, args[0]).catch(ignoreDuplicateNavigation)
}) as typeof originalPush

const originalReplace = Router.prototype.replace
Router.prototype.replace = (function(this: Router, ...args: Parameters<typeof originalReplace>) {
	if (args.length > 1) {
		return originalReplace.call(this, ...args)
	}
	return originalReplace.call<Router, [RawLocation], Promise<Route>>(this, args[0]).catch(ignoreDuplicateNavigation)
}) as typeof originalReplace

/**
 * Ignore duplicated-navigation error but forward real exceptions
 * @param error The thrown error
 */
function ignoreDuplicateNavigation(error: unknown): void {
	if (isNavigationFailure(error, NavigationFailureType.duplicated)) {
		logger.debug('Ignoring duplicated navigation from vue-router', { error })
	} else {
		throw error
	}
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
			redirect: { name: 'filelist', params: { view: 'files' } },
		},
		{
			path: '/:view/:fileid(\\d+)?',
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
