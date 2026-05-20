/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import moment from '@nextcloud/moment'
import { generateUrl } from '@nextcloud/router'
import Vue, { type ComponentPublicInstance } from 'vue'
import logger from './logger.js'
import { getComments } from './services/GetComments.js'
import { markCommentsAsRead } from './services/ReadComments.js'

import { PiniaVuePlugin, createPinia } from 'pinia'

Vue.use(PiniaVuePlugin)

let ActivityTabPluginView
let ActivityTabPluginInstance

/**
 * Register the comments plugins for the Activity sidebar
 */
export function registerCommentsPlugins() {
	window.OCA.Activity.registerSidebarAction({
		mount: async (el, { context, fileInfo, reload }) => {
			const pinia = createPinia()

			if (!ActivityTabPluginView) {
				const { default: ActivityCommentAction } = await import('./views/ActivityCommentAction.vue')
				// @ts-expect-error Types are broken for Vue2
				ActivityTabPluginView = Vue.extend(ActivityCommentAction)
			}
			ActivityTabPluginInstance = new ActivityTabPluginView({
				el,
				parent: context,
				pinia,
				propsData: {
					reloadCallback: reload,
					resourceId: fileInfo.id,
				},
			})
			logger.info('Comments plugin mounted in Activity sidebar action', { fileInfo })
		},
		unmount: () => {
			// destroy previous instance if available
			if (ActivityTabPluginInstance) {
				ActivityTabPluginInstance.$destroy()
			}
		},
	})

	window.OCA.Activity.registerSidebarEntries(async ({ fileInfo, limit, offset }) => {
		const { data: comments } = await getComments({ resourceType: 'files', resourceId: fileInfo.id }, { limit, offset })
		logger.debug('Loaded comments', { fileInfo, comments })

		// Optimistically clear the unread bubble in the file list immediately
		// (without waiting for the PROPPATCH to complete), so the UI updates
		// without requiring a page refresh.
		// fileInfo.node is the underlying @nextcloud/files Node set by the Files sidebar.
		// Optimistically clear the unread bubble immediately via the global event bus
		// (window._nc_event_bus) so the UI updates without a page refresh.
		// fileInfo.node is the underlying @nextcloud/files Node set by the Files sidebar.
		const node = fileInfo.node
		if (node) {
			node.attributes['comments-unread'] = 0
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
			;(window as any)._nc_event_bus?.emit('files:node:updated', node)
		}
		markCommentsAsRead('files', fileInfo.id, new Date()).catch(() => {})

		// Mark mention notifications as read for comments that mention the current user
		const currentUser = getCurrentUser()
		if (currentUser) {
			for (const comment of comments) {
				const mentions = Object.values(comment.props?.mentions ?? {}) as { mentionType: string, mentionId: string }[]
				const isMentioned = comment.props?.id && mentions.some((m) => m.mentionType === 'user' && m.mentionId === currentUser.uid)
				if (isMentioned) {
					axios.delete(generateUrl('/apps/comments/notifications/{id}', { id: comment.props.id }))
						.catch(() => {})
				}
			}
		}

		const { default: CommentView } = await import('./views/ActivityCommentEntry.vue')
		// @ts-expect-error Types are broken for Vue2
		const CommentsViewObject = Vue.extend(CommentView)

		return comments.map((comment) => ({
			_CommentsViewInstance: undefined as ComponentPublicInstance | undefined,

			timestamp: moment(comment.props?.creationDateTime).toDate().getTime(),

			mount(element: HTMLElement, { context, reload }) {
				this._CommentsViewInstance = new CommentsViewObject({
					el: element,
					parent: context,
					propsData: {
						comment,
						resourceId: fileInfo.id,
						reloadCallback: reload,
					},
				})
			},
			unmount() {
				this._CommentsViewInstance?.$destroy()
			},
		}))
	})

	window.OCA.Activity.registerSidebarFilter((activity) => activity.type !== 'comments')
	logger.info('Comments plugin registered for Activity sidebar action')
}
