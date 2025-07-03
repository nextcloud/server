/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node } from '@nextcloud/files'
import { registerDavProperty } from '@nextcloud/files/dav'

/**
 * Registers the Live Photos service by adding a DAV property for live photos metadata.
 * This allows the Nextcloud Files app to recognize and handle live photos.
 */
export function registerLivePhotosService(): void {
	registerDavProperty('nc:metadata-files-live-photo', { nc: 'http://nextcloud.org/ns' })
}

/**
 * @param {Node} node - The node
 */
export function isLivePhoto(node: Node): boolean {
	return node.attributes['metadata-files-live-photo'] !== undefined
}
