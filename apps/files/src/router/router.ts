/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { RawLocation, Route } from 'vue-router'

import { generateUrl } from '@nextcloud/router'
import { relative } from 'path'
import queryString from 'query-string'
import Router, { isNavigationFailure, NavigationFailureType } from 'vue-router'
import Vue from 'vue'

import { useFilesStore } from '../store/files'
import { useNavigation } from '../composables/useNavigation'
import { usePathsStore } from '../store/paths'
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

// If navigating back from a folder to a parent folder,
// we need to keep the current dir fileid so it's highlighted
// and scrolled into view.
router.beforeEach((to, from, next) => {
	if (to.params?.parentIntercept) {
		delete to.params.parentIntercept
		next()
		return
	}

	const fromDir = (from.query?.dir || '/') as string
	const toDir = (to.query?.dir || '/') as string

	// We are going back to a parent directory
	if (relative(fromDir, toDir) === '..') {
		const { currentView } = useNavigation()
		const { getNode } = useFilesStore()
		const { getPath } = usePathsStore()

		if (!currentView.value?.id) {
			logger.error('No current view id found, cannot navigate to parent directory', { fromDir, toDir })
			return next()
		}

		// Get the previous parent's file id
		const fromSource = getPath(currentView.value?.id, fromDir)
		if (!fromSource) {
			logger.error('No source found for the parent directory', { fromDir, toDir })
			return next()
		}

		const fileId = getNode(fromSource)?.fileid
		if (!fileId) {
			logger.error('No fileid found for the parent directory', { fromDir, toDir, fromSource })
			return next()
		}

		logger.debug('Navigating back to parent directory', { fromDir, toDir, fileId })
		next({
			name: 'filelist',
			query: to.query,
			params: {
				...to.params,
				fileid: String(fileId),
				// Prevents the beforeEach from being called again
				parentIntercept: 'true',
			},
			// Replace the current history entry
			replace: true,
		})
	}

	// else, we just continue
	next()
})

export default router
