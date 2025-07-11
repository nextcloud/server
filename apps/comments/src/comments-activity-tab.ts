/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import moment from '@nextcloud/moment'
import Vue, { type ComponentPublicInstance } from 'vue'
import logger from './logger.js'
import { getComments } from './services/GetComments.js'

import { PiniaVuePlugin, createPinia } from 'pinia'

Vue.use(PiniaVuePlugin)

let ActivityTabPluginView
let ActivityTabPluginInstance

/**
 * Register the comments plugins for the Activity sidebar
 */
export function registerCommentsPlugins() {
	window.OCA.Activity.registerSidebarAction({
		mount: async (el, { fileInfo, reload }) => {
			const pinia = createPinia()

			if (!ActivityTabPluginView) {
				const { default: ActivityCommentAction } = await import('./views/ActivityCommentAction.vue')
				// @ts-expect-error Types are broken for Vue2
				ActivityTabPluginView = Vue.extend(ActivityCommentAction)
			}
			ActivityTabPluginInstance = new ActivityTabPluginView({
				el,
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
		const { default: CommentView } = await import('./views/ActivityCommentEntry.vue')
		// @ts-expect-error Types are broken for Vue2
		const CommentsViewObject = Vue.extend(CommentView)

		return comments.map((comment) => ({
			_CommentsViewInstance: undefined as ComponentPublicInstance | undefined,

			timestamp: moment(comment.props?.creationDateTime).toDate().getTime(),

			mount(element: HTMLElement, { reload }) {
				this._CommentsViewInstance = new CommentsViewObject({
					el: element,
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
