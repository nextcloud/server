/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node } from '@nextcloud/files'
import type { ShareAttribute } from '../../../files_sharing/src/sharing.ts'

import { Permission } from '@nextcloud/files'

/**
 * Check permissions on the node if it can be downloaded
 * @param node The node to check
 * @return True if downloadable, false otherwise
 */
export function isDownloadable(node: Node): boolean {
	if ((node.permissions & Permission.READ) === 0) {
		return false
	}

	// If the mount type is a share, ensure it got download permissions.
	if (node.attributes['share-attributes']) {
		const shareAttributes = JSON.parse(node.attributes['share-attributes'] || '[]') as Array<ShareAttribute>
		const downloadAttribute = shareAttributes.find(({ scope, key }: ShareAttribute) => scope === 'permissions' && key === 'download')
		if (downloadAttribute !== undefined) {
			return downloadAttribute.value === true
		}
	}

	return true
}
