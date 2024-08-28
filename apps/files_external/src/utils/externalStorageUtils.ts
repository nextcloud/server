/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { FileType, Node } from '@nextcloud/files'
import type { MountEntry } from '../services/externalStorage'

export const isNodeExternalStorage = function(node: Node) {
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
