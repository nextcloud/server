/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'

import VersionTab from './views/VersionTab.vue'
import VTooltipPlugin from 'v-tooltip'
// eslint-disable-next-line n/no-missing-import, import/no-unresolved
import BackupRestore from '@mdi/svg/svg/backup-restore.svg?raw'

Vue.prototype.t = t
Vue.prototype.n = n

Vue.use(VTooltipPlugin)

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
			if (!TabInstance) {
				return
			}
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
