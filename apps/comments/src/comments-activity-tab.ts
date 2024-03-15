/**
 * @copyright Copyright (c) 2023 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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
import moment from '@nextcloud/moment'
import Vue from 'vue'
import logger from './logger.js'
import { getComments } from './services/GetComments.js'

let ActivityTabPluginView
let ActivityTabPluginInstance

/**
 * Register the comments plugins for the Activity sidebar
 */
export function registerCommentsPlugins() {
	window.OCA.Activity.registerSidebarAction({
		mount: async (el, { context, fileInfo, reload }) => {
			if (!ActivityTabPluginView) {
				const { default: ActivityCommmentAction } = await import('./views/ActivityCommentAction.vue')
				ActivityTabPluginView = Vue.extend(ActivityCommmentAction)
			}
			ActivityTabPluginInstance = new ActivityTabPluginView({
				parent: context,
				propsData: {
					reloadCallback: reload,
					resourceId: fileInfo.id,
				},
			})
			ActivityTabPluginInstance.$mount(el)
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
		const CommentsViewObject = Vue.extend(CommentView)

		return comments.map((comment) => ({
			timestamp: moment(comment.props.creationDateTime).toDate().getTime(),
			mount(element, { context, reload }) {
				this._CommentsViewInstance = new CommentsViewObject({
					parent: context,
					propsData: {
						comment,
						resourceId: fileInfo.id,
						reloadCallback: reload,
					},
				})
				this._CommentsViewInstance.$mount(element)
			},
			unmount() {
				this._CommentsViewInstance.$destroy()
			},
		}))
	})

	window.OCA.Activity.registerSidebarFilter((activity) => activity.type !== 'comments')
	logger.info('Comments plugin registered for Activity sidebar action')
}
