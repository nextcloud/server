/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import { createRouter, createWebHistory, isNavigationFailure, NavigationFailureType, stringifyQuery } from 'vue-router'
import logger from '../services/logger.ts'

const view = loadState<string>('files_sharing', 'view')
const sharingToken = loadState<string>('files_sharing', 'sharingToken')

const FilesListComponent = () => import('~/apps/files/src/views/FilesList.vue')

/**
 * Stringify the query the way Nextcloud URLs have always looked: spaces as
 * "%20" rather than "+". A literal "+" is already encoded as "%2B" by then, so
 * this cannot corrupt a file name.
 *
 * @param query - The query to stringify
 */
function stringifyNextcloudQuery(query: Parameters<typeof stringifyQuery>[0]): string {
	return stringifyQuery(query).replace(/\+/g, '%20')
}

const router = createRouter({
	// if index.php is in the url AND we got this far, then it's working:
	// let's keep using index.php in the url
	history: createWebHistory(generateUrl('/s')),
	linkActiveClass: 'active',
	stringifyQuery: stringifyNextcloudQuery,

	routes: [
		{
			path: '/',
			// Pretending we're using the default view
			redirect: { name: 'filelist', params: { view, token: sharingToken } },
		},
		{
			path: '/:token',
			name: 'filelist',
			component: FilesListComponent,
			props: { isPublic: true },
		},
	],
})

// Handle aborted navigation (NavigationGuards) gracefully
router.onError((error) => {
	if (isNavigationFailure(error, NavigationFailureType.aborted)) {
		logger.debug('Navigation was aborted', { error })
	} else {
		throw error
	}
})

export default router
