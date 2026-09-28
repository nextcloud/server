/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// eslint-disable-next-line n/no-missing-import, import/no-unresolved
import MessageReplyText from '@mdi/svg/svg/message-reply-text.svg?raw'
import { getCSPNonce } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import { registerCommentsPlugins } from './comments-activity-tab.ts'

// @ts-expect-error __webpack_nonce__ is injected by webpack
__webpack_nonce__ = getCSPNonce()

if (loadState('comments', 'activityEnabled', false) && OCA?.Activity?.registerSidebarAction !== undefined) {
	// Do not mount own tab but mount into activity
	window.addEventListener('DOMContentLoaded', function() {
		registerCommentsPlugins()
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
				propsData: {
					resourceId: fileInfo.id,
				},
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
