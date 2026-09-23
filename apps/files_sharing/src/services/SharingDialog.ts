/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Node } from '@nextcloud/files'

import { getShare, isSharingDialogAvailable, openSharingDialog } from '@nextcloud/sharing/dialog'

/**
 * The dialog is a Vue 3 component while this frontend is still Vue 2. The
 * library depends on Vue itself, so npm installs its own copy next to it and
 * the dialog runs on that one, the same way `@nextcloud/dialogs` does.
 */

export { isSharingDialogAvailable }

/**
 * Open the unified sharing dialog to create a new share for a node.
 *
 * @param node The file or folder to share
 */
export function openShareCreateDialog(node: Node): Promise<unknown> {
	return openSharingDialog(node)
}

/**
 * Open the unified sharing dialog to edit an existing share.
 *
 * @param shareId The share id (mapped to the unified API by the legacy bridge)
 * @param node The backing node, used for the dialog title
 */
export async function openShareEditDialog(shareId: string | number, node?: Node): Promise<unknown> {
	const share = await getShare(String(shareId))
	return share.showDialog(node)
}
