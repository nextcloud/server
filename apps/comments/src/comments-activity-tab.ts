/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'

import moment from '@nextcloud/moment'
import { createPinia } from 'pinia'
import { type ComponentPublicInstance, createApp } from 'vue'
import logger from './logger.ts'
import { getComments } from './services/GetComments.ts'

/**
 * Register the comments plugins for the Activity sidebar
 */
export function registerCommentsPlugins() {
	let app

	window.OCA.Activity.registerSidebarAction({
		mount: async (el: HTMLElement, { node, reload }: { node: INode, reload: () => void }) => {
			const pinia = createPinia()

			if (!app) {
				const { default: ActivityCommentAction } = await import('./views/ActivityCommentAction.vue')
				app = createApp(
					ActivityCommentAction,
					{
						reloadCallback: reload,
						resourceId: node.fileid,
					},
				)
			}
			app.use(pinia)
			app.mount(el)
			logger.info('Comments plugin mounted in Activity sidebar action', { node })
		},
		unmount: () => {
			// destroy previous instance if available
			app?.unmount()
		},
	})

	window.OCA.Activity.registerSidebarEntries(async ({ node, limit, offset }: { node: INode, limit?: number, offset?: number }) => {
		const { data: comments } = await getComments(
			{ resourceType: 'files', resourceId: node.fileid },
			{
				limit,
				offset: offset ?? 0,
			},
		)
		logger.debug('Loaded comments', { node, comments })
		const { default: CommentView } = await import('./views/ActivityCommentEntry.vue')

		return comments.map((comment) => ({
			_CommentsViewInstance: undefined as ComponentPublicInstance | undefined,

			timestamp: moment(comment.props?.creationDateTime).toDate().getTime(),

			mount(element: HTMLElement, { reload }) {
				this._CommentsViewInstance = createApp(
					CommentView,
					{
						comment,
						resourceId: node.fileid,
						reloadCallback: reload,
					},
				)
				this._CommentsViewInstance.mount(el)
			},
			unmount() {
				this._CommentsViewInstance?.unmount()
			},
		}))
	})

	window.OCA.Activity.registerSidebarFilter((activity) => activity.type !== 'comments')
	logger.info('Comments plugin registered for Activity sidebar action')
}
