/*
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'

import { subscribe } from '@nextcloud/event-bus'
import { generateUrl } from '@nextcloud/router'
import { relative } from 'path'
import { createRouter, createWebHistory, isNavigationFailure, NavigationFailureType } from 'vue-router'
import { useFilesStore } from '../store/files.ts'
import { pinia } from '../store/index.ts'
import { usePathsStore } from '../store/paths.ts'
import { defaultView } from '../utils/filesViews.ts'
import { logger } from '../utils/logger.ts'

const FilesListComponent = () => import('../views/FilesList.vue')

export const router = createRouter({
	// if index.php is in the url AND we got this far, then it's working:
	// let's keep using index.php in the url
	history: createWebHistory(generateUrl('/apps/files')),
	linkActiveClass: 'active',
	routes: [
		{
			path: '/',
			// Pretending we're using the default view
			redirect: { name: 'filelist', params: { view: defaultView() } },
		},
		{
			path: '/:view/:fileid(\\d+)?',
			name: 'filelist',
			props: true,
			component: FilesListComponent,
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

// If navigating back from a folder to a parent folder,
// we need to keep the current dir fileid so it's highlighted
// and scrolled into view.
router.beforeResolve((to, from) => {
	if (to.params.view !== from.params.view) {
		// skip if different views
		return
	}

	const fromDir = (from.query?.dir || '/') as string
	const toDir = (to.query?.dir || '/') as string

	// We are going back to a parent directory
	if (relative(fromDir, toDir) === '..') {
		const { getNode } = useFilesStore()
		const { getPath } = usePathsStore()

		if (!from.params.view) {
			logger.error('No current view id found, cannot navigate to parent directory', { fromDir, toDir })
			return
		}

		// Get the previous parent's file id
		const fromSource = getPath(from.params.view as string, fromDir)
		if (!fromSource) {
			logger.error('No source found for the parent directory', { fromDir, toDir })
			return
		}

		const fileId = getNode(fromSource)?.fileid
		if (to.params.fileid === String(fileId)) {
			// prevent infinite loop of navigating to the same parent directory
			return
		}

		if (!fileId) {
			logger.error('No fileid found for the parent directory', { fromDir, toDir, fromSource })
			return
		}

		logger.debug('Navigating back to parent directory', { fromDir, toDir, fileId })
		return {
			name: 'filelist',
			query: to.query,
			params: {
				...to.params,
				fileid: String(fileId),
			},
			// Replace the current history entry
			replace: true,
		}
	}
})

subscribe('files:node:deleted', (node: INode) => {
	if (router.currentRoute.value.params.fileid === String(node.fileid)) {
		const params = { ...router.currentRoute.value.params }
		const { getPath } = usePathsStore(pinia)
		const { getNode } = useFilesStore(pinia)
		const source = getPath(router.currentRoute.value.params.view as string, node.dirname)
		const parentFolder = getNode(source!)
		if (source && parentFolder) {
			params.fileid = String(parentFolder.fileid)
		} else {
			delete params.fileid
		}

		const query = { ...router.currentRoute.value.query }
		delete query.opendetails
		delete query.openfile

		router.replace({
			name: router.currentRoute.value.name as string,
			params,
			query,
		})
	}
})
