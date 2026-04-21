/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'
import type { App } from 'vue'

import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { createPinia } from 'pinia'
import { createApp } from 'vue'
import logger from './logger.ts'
import { getComments } from './services/GetComments.ts'

/**
 * Register the comments plugins for the Activity sidebar
 */
export function registerCommentsPlugins() {
	let app: App

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

		// Mark mention notifications as read for comments that mention the current user
		const currentUser = getCurrentUser()
		if (currentUser) {
			for (const comment of comments) {
				const mentions = Object.values(comment.props?.mentions ?? {}) as { mentionType: string, mentionId: string }[]
				const isMentioned = comment.props?.id && mentions.some((m) => m.mentionType === 'user' && m.mentionId === currentUser.uid)
				if (isMentioned) {
					axios.delete(generateUrl('/apps/comments/notifications/dismiss/{id}', { id: comment.props.id }))
						.catch(() => {})
				}
			}
		}
		const { default: CommentView } = await import('./views/ActivityCommentEntry.vue')

		return comments.map((comment) => ({
			_CommentsViewInstance: undefined as App | undefined,

			timestamp: Date.parse(comment.props?.creationDateTime as string | undefined ?? ''),

			mount(element: HTMLElement, { reload }) {
				const app = createApp(
					CommentView,
					{
						comment,
						resourceId: node.fileid,
						reloadCallback: reload,
					},
				)
				app.mount(element)
				this._CommentsViewInstance = app
			},
			unmount() {
				this._CommentsViewInstance?.unmount()
			},
		}))
	})

	window.OCA.Activity.registerSidebarFilter((activity) => activity.type !== 'comments')
	logger.info('Comments plugin registered for Activity sidebar action')
}
