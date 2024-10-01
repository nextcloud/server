/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { Node, registerDavProperty } from '@nextcloud/files'

/**
 *
 */
export function initLivePhotos(): void {
	registerDavProperty('nc:metadata-files-live-photo', { nc: 'http://nextcloud.org/ns' })
}

/**
 * @param {Node} node - The node
 */
export function isLivePhoto(node: Node): boolean {
	return node.attributes['metadata-files-live-photo'] !== undefined
}
