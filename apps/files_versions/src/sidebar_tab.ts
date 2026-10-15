/*!
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import BackupRestore from '@mdi/svg/svg/backup-restore.svg?raw'
import { FileType, registerSidebarTab } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { isPublicShare } from '@nextcloud/sharing/public'
import { defineAsyncComponent, defineCustomElement } from 'vue'

const tagName = 'files-versions_sidebar-tab'

registerSidebarTab({
	id: 'files_versions',
	tagName,
	order: 90,
	displayName: t('files_versions', 'Versions'),
	iconSvgInline: BackupRestore,
	enabled({ node }) {
		if (isPublicShare()) {
			return false
		}
		if (node.type !== FileType.File) {
			return false
		}
		return true
	},

	async onInit() {
		const FilesVersionsSidebarTab = defineAsyncComponent(() => import('./views/FilesVersionsSidebarTab.vue'))
		window.customElements.define(tagName, defineCustomElement(FilesVersionsSidebarTab, {
			shadowRoot: false,
		}))
	},
})
