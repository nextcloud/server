/*!
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import MessageReplyText from '@mdi/svg/svg/message-reply-text.svg?raw'
import { registerSidebarTab } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { createPinia } from 'pinia'
import { defineCustomElement } from 'vue'
import { registerCommentsPlugins } from './comments-activity-tab.ts'
import { isUsingActivityIntegration } from './utils/activity.ts'

const tagName = 'comments_files-sidebar-tab'

if (isUsingActivityIntegration()) {
	// Do not mount own tab but mount into activity
	window.addEventListener('DOMContentLoaded', function() {
		registerCommentsPlugins()
	})
} else {
	registerSidebarTab({
		id: 'comments',
		displayName: t('comments', 'Comments'),
		iconSvgInline: MessageReplyText,
		order: 50,
		tagName,
		async onInit() {
			const { default: FilesSidebarTab } = await import('./views/FilesSidebarTab.vue')

			const FilesSidebarTabElement = defineCustomElement(FilesSidebarTab, {
				configureApp(app) {
					const pinia = createPinia()
					app.use(pinia)
				},
				shadowRoot: false,
			})

			window.customElements.define(tagName, FilesSidebarTabElement)
		},
	})
}
