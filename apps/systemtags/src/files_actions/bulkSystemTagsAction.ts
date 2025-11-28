/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Node } from '@nextcloud/files'

import TagMultipleSvg from '@mdi/svg/svg/tag-multiple-outline.svg?raw'
import { FileAction, Permission } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { isPublicShare } from '@nextcloud/sharing/public'
import { spawnDialog } from '@nextcloud/vue/functions/dialog'
import { defineAsyncComponent } from 'vue'

/**
 * Spawn a dialog to add or remove tags from multiple nodes.
 *
 * @param nodes Nodes to modify tags for
 * @param nodes.nodes
 */
async function execBatch({ nodes }: { nodes: Node[] }): Promise<(null | boolean)[]> {
	const response = await new Promise<null | boolean>((resolve) => {
		spawnDialog(defineAsyncComponent(() => import('../components/SystemTagPicker.vue')), {
			nodes,
		}, (status) => {
			resolve(status as null | boolean)
		})
	})
	return Array(nodes.length).fill(response)
}

export const action = new FileAction({
	id: 'systemtags:bulk',
	displayName: () => t('systemtags', 'Manage tags'),
	iconSvgInline: () => TagMultipleSvg,

	// If the app is disabled, the action is not available anyway
	enabled({ nodes }) {
		if (isPublicShare()) {
			return false
		}

		if (nodes.length === 0) {
			return false
		}

		// Disabled for non dav resources
		if (nodes.some((node) => !node.isDavResource)) {
			return false
		}

		// We need to have the update permission on all nodes
		return !nodes.some((node) => (node.permissions & Permission.UPDATE) === 0)
	},

	async exec({ nodes }) {
		return execBatch({ nodes })[0]
	},

	execBatch,
})
