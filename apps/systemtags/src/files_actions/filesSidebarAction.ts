/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import tagSvg from '@mdi/svg/svg/tag-outline.svg?raw'
import { registerSidebarAction } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { spawnDialog } from '@nextcloud/vue/functions/dialog'
import { defineAsyncComponent } from 'vue'

/**
 * Register the "Add tags" action in the file sidebar
 */
export function registerFileSidebarAction() {
	registerSidebarAction({
		id: 'systemtags',
		order: 20,
		displayName() {
			return t('systemtags', 'Add tags')
		},
		enabled() {
			return true
		},
		iconSvgInline() {
			return tagSvg
		},
		onClick({ node }) {
			return spawnDialog(
				defineAsyncComponent(() => import('../components/SystemTagPicker.vue')),
				{
					nodes: [node],
				},
			)
		},
	})
}
