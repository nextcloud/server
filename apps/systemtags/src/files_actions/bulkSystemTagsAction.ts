/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { type Node } from '@nextcloud/files'

import { defineAsyncComponent } from 'vue'
import { getCurrentUser } from '@nextcloud/auth'
import { FileAction } from '@nextcloud/files'
import { spawnDialog } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import TagMultipleSvg from '@mdi/svg/svg/tag-multiple.svg?raw'

export const action = new FileAction({
	id: 'systemtags:bulk',
	displayName: () => t('systemtags', 'Manage tags'),
	iconSvgInline: () => TagMultipleSvg,

	// If the app is disabled, the action is not available anyway
	enabled(nodes) {
		if (nodes.length === 0) {
			return false
		}

		// If the user is not logged in, the action is not available
		return getCurrentUser() !== null
	},

	async exec() {
		return null
	},

	async execBatch(nodes: Node[]) {
		const response = await new Promise<null|boolean>((resolve) => {
			spawnDialog(defineAsyncComponent(() => import('../components/SystemTagPicker.vue')), {
				nodes,
			}, (status) => {
				resolve(status as null|boolean)
			})
		})
		return Array(nodes.length).fill(response)
	},
})
