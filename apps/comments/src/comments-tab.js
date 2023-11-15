/**
 * @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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

// eslint-disable-next-line n/no-missing-import, import/no-unresolved
import MessageReplyText from '@mdi/svg/svg/message-reply-text.svg?raw'
// eslint-disable-next-line import/no-unresolved
import trashbinSVG from '@mdi/svg/svg/trash-can.svg?raw'
import { getCurrentUser, getRequestToken } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import Vue from 'vue'
import logger from './logger.js'
import deleteComment from './services/DeleteComment.js'

// @ts-expect-error __webpack_nonce__ is injected by webpack
__webpack_nonce__ = btoa(getRequestToken())

if (loadState('comments', 'activityEnabled', true) && OCA?.Activity?.registerSidebarAction !== undefined) {
	let ActivityTabPluginView
	let ActivityTabPluginInstance

	// Do mount own tab but mount into activity
	window.addEventListener('DOMContentLoaded', function() {
		OCA.Activity.registerSidebarAction({
			mount: async (el, { context, fileInfo, reload }) => {
				if (!ActivityTabPluginView) {
					const { default: ActivityCommmentAction } = await import('./views/ActivityCommentAction.vue')
					ActivityTabPluginView = Vue.extend(ActivityCommmentAction)
				}
				ActivityTabPluginInstance = new ActivityTabPluginView({
					parent: context,
					propsData: {
						reloadCallback: reload,
						ressourceId: fileInfo.id,
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

		OCA.Activity.registerAction('comments', ({ activity, reload }) => {
			const actions = []
			if (activity.user === getCurrentUser()?.uid) {
				actions.push({
					label: t('comments', 'Delete comment'),
					icon: trashbinSVG,
					handler: async (activity) => {
						await deleteComment(...activity.link.split('/').slice(-3))
						reload()
					},
				})
			}
			return actions
		})
		logger.info('Comments plugin registered for Activity sidebar action')
	})
} else {
	// Init Comments tab component
	let TabInstance = null
	const commentTab = new OCA.Files.Sidebar.Tab({
		id: 'comments',
		name: t('comments', 'Comments'),
		iconSvg: MessageReplyText,

		async mount(el, fileInfo, context) {
			if (TabInstance) {
				TabInstance.$destroy()
			}
			TabInstance = new OCA.Comments.View('files', {
				// Better integration with vue parent component
				parent: context,
			})
			// Only mount after we have all the info we need
			await TabInstance.update(fileInfo.id)
			TabInstance.$mount(el)
		},
		update(fileInfo) {
			TabInstance.update(fileInfo.id)
		},
		destroy() {
			TabInstance.$destroy()
			TabInstance = null
		},
		scrollBottomReached() {
			TabInstance.onScrollBottomReached()
		},
	})

	window.addEventListener('DOMContentLoaded', function() {
		if (OCA.Files && OCA.Files.Sidebar) {
			OCA.Files.Sidebar.registerTab(commentTab)
		}
	})
}
