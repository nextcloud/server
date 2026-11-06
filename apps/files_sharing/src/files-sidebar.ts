/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import ShareVariant from '@mdi/svg/svg/share-variant.svg?raw'
import { getSidebar } from '@nextcloud/files'
import { n, t } from '@nextcloud/l10n'
import { defineCustomElement } from 'vue'
import ExternalShareActions from './services/ExternalShareActions.js'
import ShareSearch from './services/ShareSearch.js'
import TabSections from './services/TabSections.js'

// Init Sharing Tab Service
window.OCA.Sharing ??= {}
Object.assign(window.OCA.Sharing, { ShareSearch: new ShareSearch() })
Object.assign(window.OCA.Sharing, { ExternalShareActions: new ExternalShareActions() })
Object.assign(window.OCA.Sharing, { ShareTabSections: new TabSections() })

const tagName = 'files_sharing-sidebar-tab'

getSidebar().registerTab({
	id: 'sharing',
	displayName: t('files_sharing', 'Sharing'),
	iconSvgInline: ShareVariant,
	order: 10,
	tagName,

	async onInit() {
		const { default: FilesSidebarTab } = await import('./views/FilesSidebarTab.vue')
		window.customElements.define(tagName, defineCustomElement(FilesSidebarTab, {
			shadowRoot: false,
			configureApp(app) {
				app.config.globalProperties.t = t
				app.config.globalProperties.n = n
			},
		}))
	},
})
