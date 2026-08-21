/*
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'
import type { Router, RouterHistory } from 'vue-router'

import { subscribe } from '@nextcloud/event-bus'
import { generateUrl } from '@nextcloud/router'
import { relative } from 'path'
import { createRouter as createVueRouter, createWebHistory, isNavigationFailure, NavigationFailureType, stringifyQuery } from 'vue-router'
import { useFilesStore } from '../store/files.ts'
import { pinia } from '../store/index.ts'
import { usePathsStore } from '../store/paths.ts'
import { defaultView } from '../utils/filesViews.ts'
import { logger } from '../utils/logger.ts'

const FilesListComponent = () => import('../views/FilesList.vue')

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

/**
 * Create a router for the Files app or an embedded Files view.
 *
 * @param history Browser or in-memory history for this router instance
 */
export function createFilesRouter(history: RouterHistory = createWebHistory(generateUrl('/apps/files'))) {
	const filesRouter = createVueRouter({
		// if index.php is in the url AND we got this far, then it's working:
		// let's keep using index.php in the url
		history,
		linkActiveClass: 'active',
		stringifyQuery: stringifyNextcloudQuery,
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
	configureFilesRouter(filesRouter)
	return filesRouter
}

/**
 * Register Files-specific navigation handling on a router instance.
 *
 * @param filesRouter Router instance to configure
 */
function configureFilesRouter(filesRouter: Router): void {
	// Handle aborted navigation (NavigationGuards) gracefully
	filesRouter.onError((error) => {
		if (isNavigationFailure(error, NavigationFailureType.aborted)) {
			logger.debug('Navigation was aborted', { error })
		} else {
			throw error
		}
	})

	// If navigating to a parent folder, keep the current file highlighted.
	filesRouter.beforeResolve((to, from) => {
		if (to.params.view !== from.params.view) {
			// Skip if navigating to a different view.
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
				// Prevent navigating repeatedly to the same parent directory.
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
}

let filesAppRouter: Router | undefined

/**
 * Get the router of the Files app's own page.
 *
 * Created on first use, as creating a browser history router
 * rewrites the URL of the current page.
 */
export function getFilesAppRouter(): Router {
	filesAppRouter ??= createFilesRouter()
	return filesAppRouter
}

// Navigate through the router service, so stores reacting to the same deletion
// (e.g. the sidebar closing) build on this navigation instead of the outdated route.
subscribe('files:node:deleted', (node: INode) => {
	const { Router } = window.OCP.Files
	// no file list is rendered on the current page
	if (!Router) {
		return
	}

	if (Router.params.fileid === String(node.fileid)) {
		const params = { ...Router.params }
		const { getPath } = usePathsStore(pinia)
		const { getNode } = useFilesStore(pinia)
		const source = getPath(Router.params.view, node.dirname)
		const parentFolder = getNode(source!)
		if (source && parentFolder) {
			params.fileid = String(parentFolder.fileid)
		} else {
			delete params.fileid
		}

		const query = { ...Router.query }
		delete query.opendetails
		delete query.openfile

		Router.goToRoute(null, params, query, true)
	}
})
