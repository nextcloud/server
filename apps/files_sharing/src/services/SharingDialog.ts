/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Node } from '@nextcloud/files'

import { getCapabilities } from '@nextcloud/capabilities'

/**
 * The unified sharing dialog is a Vue 3 component. It is registered on the
 * global `OCA.Sharing` namespace by the Vue 3 bridge entry point
 * (`sharing-dialog-bridge.ts`), so this Vue 2 frontend can trigger it without
 * pulling Vue 3 into its bundle.
 */
type SharingDialogApi = {
	openSharingDialog(node: Node): Promise<unknown>
	openShareEditDialog(shareId: string | number, node?: Node): Promise<unknown>
}

/**
 * Get the sharing dialog API registered by the Vue 3 bridge, if loaded.
 */
function sharingDialogApi(): SharingDialogApi | undefined {
	return (window.OCA?.Sharing as Partial<SharingDialogApi> | undefined) as SharingDialogApi | undefined
}

/**
 * Whether the server provides the unified sharing API this dialog talks to.
 * Mirrors the library check so the sidebar can gate on it without importing
 * the Vue 3 code.
 */
export function isSharingDialogAvailable(): boolean {
	const capabilities = getCapabilities() as { sharing?: { api_versions?: unknown[] } }
	return (capabilities.sharing?.api_versions?.length ?? 0) > 0
}

/**
 * Open the unified sharing dialog to create a new share for a node.
 *
 * @param node The file or folder to share
 */
export function openShareCreateDialog(node: Node): Promise<unknown> {
	const api = sharingDialogApi()
	if (!api?.openSharingDialog) {
		return Promise.reject(new Error('The unified sharing dialog is not available'))
	}
	return api.openSharingDialog(node)
}

/**
 * Open the unified sharing dialog to edit an existing share.
 *
 * @param shareId The share id (mapped to the unified API by the legacy bridge)
 * @param node The backing node, used for the dialog title
 */
export function openShareEditDialog(shareId: string | number, node?: Node): Promise<unknown> {
	const api = sharingDialogApi()
	if (!api?.openShareEditDialog) {
		return Promise.reject(new Error('The unified sharing dialog is not available'))
	}
	return api.openShareEditDialog(shareId, node)
}
