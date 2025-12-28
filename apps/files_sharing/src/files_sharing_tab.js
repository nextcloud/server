/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import ShareVariant from '@mdi/svg/svg/share-variant.svg?raw'
import { getCSPNonce } from '@nextcloud/auth'
import { registerSidebarTab } from '@nextcloud/files'
import { n, t } from '@nextcloud/l10n'
import wrap from '@vue/web-component-wrapper'
import Vue from 'vue'
import FilesSidebarTab from './views/FilesSidebarTab.vue'
import ExternalShareActions from './services/ExternalShareActions.js'
import ShareSearch from './services/ShareSearch.js'
import TabSections from './services/TabSections.js'

__webpack_nonce__ = getCSPNonce()

// Init Sharing Tab Service
window.OCA.Sharing ??= {}
Object.assign(window.OCA.Sharing, { ShareSearch: new ShareSearch() })
Object.assign(window.OCA.Sharing, { ExternalShareActions: new ExternalShareActions() })
Object.assign(window.OCA.Sharing, { ShareTabSections: new TabSections() })

Vue.prototype.t = t
Vue.prototype.n = n

const tagName = 'files_sharing-sidebar-tab'

registerSidebarTab({
	id: 'sharing',
	displayName: t('files_sharing', 'Sharing'),
	iconSvgInline: ShareVariant,
	order: 10,
	tagName,
	enabled() {
		if (!window.customElements.get(tagName)) {
			setupSidebarTab()
		}
		return true
	},
})

/**
 * Setup the sidebar tab as a web component
 */
function setupSidebarTab() {
	const webComponent = wrap(Vue, FilesSidebarTab)
	// In Vue 2, wrap doesn't support diseabling shadow. Disable with a hack
	Object.defineProperty(webComponent.prototype, 'attachShadow', {
		value() { return this },
	})
	Object.defineProperty(webComponent.prototype, 'shadowRoot', {
		get() { return this },
	})

	window.customElements.define(tagName, webComponent)
}
