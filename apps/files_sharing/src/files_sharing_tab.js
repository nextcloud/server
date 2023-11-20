/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
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

import Vue from 'vue'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import { getRequestToken } from '@nextcloud/auth'

import ShareSearch from './services/ShareSearch.js'
import ExternalLinkActions from './services/ExternalLinkActions.js'
import ExternalShareActions from './services/ExternalShareActions.js'
import TabSections from './services/TabSections.js'

// eslint-disable-next-line n/no-missing-import, import/no-unresolved
import ShareVariant from '@mdi/svg/svg/share-variant.svg?raw'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken())

// Init Sharing Tab Service
if (!window.OCA.Sharing) {
	window.OCA.Sharing = {}
}
Object.assign(window.OCA.Sharing, { ShareSearch: new ShareSearch() })
Object.assign(window.OCA.Sharing, { ExternalLinkActions: new ExternalLinkActions() })
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
				TabInstance.$destroy()
				TabInstance = null
			},
		}))
	}
})
