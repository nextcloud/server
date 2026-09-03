/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Node } from '@nextcloud/files'

import { getShare, isSharingDialogAvailable, openSharingDialog } from '@nextcloud/sharing/dialog'

/**
 * Bridge the Vue 3 unified sharing dialog into the global `OCA.Sharing`
 * namespace so the (still Vue 2) files_sharing sidebar can trigger it without
 * importing Vue 3 code into its bundle. The dialog spawns its own Vue 3 app,
 * so it must be registered from this Vue 3 entry point where `vue` and
 * `@nextcloud/vue` resolve to their Vue 3 versions.
 *
 * Once files_sharing is migrated to Vue 3 this bridge can be dropped and the
 * library imported directly.
 */

window.OCA ??= {}
window.OCA.Sharing ??= {}

Object.assign(window.OCA.Sharing, {
	openSharingDialog,

	/**
	 * Open the unified sharing dialog to edit an existing share.
	 *
	 * @param shareId The share id (mapped to the unified API by the legacy bridge)
	 * @param node The backing node, used for the dialog title
	 */
	async openShareEditDialog(shareId: string | number, node?: Node): Promise<unknown> {
		const share = await getShare(String(shareId))
		return share.showDialog(node)
	},

	isSharingDialogAvailable,
})
