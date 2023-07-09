/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
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

import VersionTab from './views/VersionTab.vue'
import VTooltip from 'v-tooltip'
// eslint-disable-next-line n/no-missing-import, import/no-unresolved
import BackupRestore from '@mdi/svg/svg/backup-restore.svg?raw'

Vue.prototype.t = t
Vue.prototype.n = n

Vue.use(VTooltip)

// Init Sharing tab component
const View = Vue.extend(VersionTab)
let TabInstance = null

window.addEventListener('DOMContentLoaded', function() {
	if (OCA.Files?.Sidebar === undefined) {
		return
	}

	OCA.Files.Sidebar.registerTab(new OCA.Files.Sidebar.Tab({
		id: 'version_vue',
		name: t('files_versions', 'Versions'),
		iconSvg: BackupRestore,

		async mount(el, fileInfo, context) {
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
		setIsActive(isActive) {
			TabInstance.setIsActive(isActive)
		},
		destroy() {
			TabInstance.$destroy()
			TabInstance = null
		},
		enabled(fileInfo) {
			return !(fileInfo?.isDirectory() ?? true)
		},
	}))
})
