/*!
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

/**
 * Remove stored option values that belonged to the previous authentication
 * mechanism and are not used by the newly selected mechanism or the backend.
 *
 * A mount keeps all backend and authentication values in a single map keyed by
 * configuration field name. When the mechanism is changed on an existing mount,
 * the previous mechanism's fields would otherwise linger as unused values.
 *
 * @param backendOptions - The stored option values, mutated in place
 * @param previousConfiguration - Configuration of the previously selected mechanism
 * @param keptConfigurations - Configurations whose field names must be preserved
 */
export function pruneUnusedAuthMechanismOptions(
	backendOptions: Record<string, unknown>,
	previousConfiguration: Record<string, unknown> | undefined,
	keptConfigurations: Array<Record<string, unknown> | undefined>,
): void {
	const kept = new Set(keptConfigurations.flatMap((configuration) => Object.keys(configuration ?? {})))
	for (const key of Object.keys(previousConfiguration ?? {})) {
		if (!kept.has(key)) {
			delete backendOptions[key]
		}
	}
}
