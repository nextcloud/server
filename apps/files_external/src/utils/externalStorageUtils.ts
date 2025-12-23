/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'
import type { MountEntry } from '../services/externalStorage.ts'

import { FileType } from '@nextcloud/files'

/**
 * Check if the given node represents an external storage mount
 *
 * @param node - The node to check
 */
export function isNodeExternalStorage(node: INode) {
	// Not a folder, not a storage
	if (node.type === FileType.File) {
		return false
	}

	// No backend or scope, not a storage
	const attributes = node.attributes as MountEntry
	if (!attributes.scope || !attributes.backend) {
		return false
	}

	// Specific markers that we're sure are ext storage only
	return attributes.scope === 'personal' || attributes.scope === 'system'
}
