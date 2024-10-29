/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { type Node } from '@nextcloud/files'

import { defineAsyncComponent } from 'vue'
import { FileAction } from '@nextcloud/files'
import { isPublicShare } from '@nextcloud/sharing/public'
import { spawnDialog } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import TagMultipleSvg from '@mdi/svg/svg/tag-multiple.svg?raw'

async function execBatch(nodes: Node[]): Promise<(null|boolean)[]> {
	const response = await new Promise<null|boolean>((resolve) => {
		spawnDialog(defineAsyncComponent(() => import('../components/SystemTagPicker.vue')), {
			nodes,
		}, (status) => {
			resolve(status as null|boolean)
		})
	})
	return Array(nodes.length).fill(response)
}

export const action = new FileAction({
	id: 'systemtags:bulk',
	displayName: () => t('systemtags', 'Manage tags'),
	iconSvgInline: () => TagMultipleSvg,

	// If the app is disabled, the action is not available anyway
	enabled(nodes) {
		if (isPublicShare()) {
			return false
		}

		if (nodes.length === 0) {
			return false
		}

		// If the user is not logged in, the action is not available
		return true
	},

	async exec(node: Node) {
		return execBatch([node])[0]
	},

	execBatch,
})
