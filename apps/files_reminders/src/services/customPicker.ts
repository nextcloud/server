/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'

import { spawnDialog } from '@nextcloud/vue/functions/dialog'

/**
 * @param node - The file or folder node to set the custom reminder for
 */
export async function pickCustomDate(node: INode): Promise<void> {
	// loaded on demand: the modal pulls in the date picker and its date-fns locales
	const { default: SetCustomReminderModal } = await import('../components/SetCustomReminderModal.vue')
	await spawnDialog(SetCustomReminderModal, {
		node,
	})
}
