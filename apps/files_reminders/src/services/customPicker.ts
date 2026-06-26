/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'

import { spawnDialog } from '@nextcloud/vue'
import SetCustomReminderModal from '../components/SetCustomReminderModal.vue'

/**
 * @param node - The file or folder node to set the custom reminder for
 */
export async function pickCustomDate(node: INode): Promise<void> {
	await spawnDialog(SetCustomReminderModal, {
		node,
	})
}
