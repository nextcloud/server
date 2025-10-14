/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import ShareVariant from '@mdi/svg/svg/share-variant.svg?raw'
import { getCSPNonce } from '@nextcloud/auth'
import { n, t } from '@nextcloud/l10n'
import Vue from 'vue'
import ExternalShareActions from './services/ExternalShareActions.js'
import ShareSearch from './services/ShareSearch.js'
import TabSections from './services/TabSections.js'

__webpack_nonce__ = getCSPNonce()

// Init Sharing Tab Service
if (!window.OCA.Sharing) {
	window.OCA.Sharing = {}
}
Object.assign(window.OCA.Sharing, { ShareSearch: new ShareSearch() })
Object.assign(window.OCA.Sharing, { ExternalShareActions: new ExternalShareActions() })
Object.assign(window.OCA.Sharing, { ShareTabSections: new TabSections() })

Vue.prototype.t = t
Vue.prototype.n = n

// Init Sharing tab component
let TabInstance = null

window.addEventListener('DOMContentLoaded', function() {
	if (OCA.Files && OCA.Files.Sidebar) {
		OCA.Files.Sidebar.registerTab(new OCA.Files.Sidebar.Tab({
			id: 'sharing',
			name: t('files_sharing', 'Sharing'),
			iconSvg: ShareVariant,

			async mount(el, fileInfo, context) {
				const SharingTab = (await import('./views/SharingTab.vue')).default
				const View = Vue.extend(SharingTab)

				if (TabInstance) {
					TabInstance.$destroy()
				}
				TabInstance = new View({
					// Better integration with vue parent component
					parent: context,
				})
				// Only mount after we have all the info we need
				await TabInstance.update(fileInfo)
				TabInstance.$mount(el)
			},

			update(fileInfo) {
				TabInstance.update(fileInfo)
			},

			destroy() {
				if (TabInstance) {
					TabInstance.$destroy()
					TabInstance = null
				}
			},
		}))
	}
})
