/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import MessageReplyText from '@mdi/svg/svg/message-reply-text.svg?raw'
import { getCSPNonce } from '@nextcloud/auth'
import { registerSidebarTab } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import wrap from '@vue/web-component-wrapper'
import { createPinia, PiniaVuePlugin } from 'pinia'
import Vue from 'vue'
import { registerCommentsPlugins } from './comments-activity-tab.ts'

__webpack_nonce__ = getCSPNonce()

const tagName = 'comments_files-sidebar-tab'

if (loadState('comments', 'activityEnabled', false) && OCA?.Activity?.registerSidebarAction !== undefined) {
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

			Vue.use(PiniaVuePlugin)
			Vue.mixin({ pinia: createPinia() })
			const webComponent = wrap(Vue, FilesSidebarTab)
			// In Vue 2, wrap doesn't support disabling shadow. Disable with a hack
			Object.defineProperty(webComponent.prototype, 'attachShadow', {
				value() { return this },
			})
			Object.defineProperty(webComponent.prototype, 'shadowRoot', {
				get() { return this },
			})
			window.customElements.define(tagName, webComponent)
		},
	})
}
