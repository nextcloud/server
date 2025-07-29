/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { RawLocation, Route } from 'vue-router'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import queryString from 'query-string'
import Router, { isNavigationFailure, NavigationFailureType } from 'vue-router'
import Vue from 'vue'
import logger from '../services/logger'

const view = loadState<string>('files_sharing', 'view')
const sharingToken = loadState<string>('files_sharing', 'sharingToken')

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
	base: generateUrl('/s'),
	linkActiveClass: 'active',

	routes: [
		{
			path: '/',
			// Pretending we're using the default view
			redirect: { name: 'filelist', params: { view, token: sharingToken } },
		},
		{
			path: '/:token',
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
