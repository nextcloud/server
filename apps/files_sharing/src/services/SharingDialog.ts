/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Node } from '@nextcloud/files'

import { getCapabilities } from '@nextcloud/capabilities'

/**
 * Whether the unified sharing API is available.
 * Same check as `isSharingDialogAvailable` from `@nextcloud/sharing/dialog`,
 * which cannot be imported without loading the whole dialog.
 */
export function isSharingDialogAvailable(): boolean {
	const capabilities = getCapabilities() as { sharing?: { api_versions?: string[] } }
	return (capabilities.sharing?.api_versions?.length ?? 0) > 0
}

/**
 * Open the unified sharing dialog to create a new share for a node.
 *
 * @param node The file or folder to share
 */
export async function openShareCreateDialog(node: Node): Promise<unknown> {
	const { openSharingDialog } = await import('@nextcloud/sharing/dialog')
	return openSharingDialog(node)
}

/**
 * Open the unified sharing dialog to edit an existing share.
 *
 * @param shareId The share id (mapped to the unified API by the legacy bridge)
 * @param node The backing node, used for the dialog title
 */
export async function openShareEditDialog(shareId: string | number, node?: Node): Promise<unknown> {
	const { getShare } = await import('@nextcloud/sharing/dialog')
	const share = await getShare(String(shareId))
	return share.showDialog(node)
}
