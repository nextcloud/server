/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Node, View } from '@nextcloud/files'

import { FileAction } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import AlarmSvg from '@mdi/svg/svg/alarm.svg?raw'

export const SET_REMINDER_MENU_ID = 'set-reminder-menu'

export const action = new FileAction({
	id: SET_REMINDER_MENU_ID,
	displayName: () => t('files_reminders', 'Set reminder'),
	iconSvgInline: () => AlarmSvg,

	enabled: (nodes: Node[], view: View) => {
		if (view.id === 'trashbin') {
			return false
		}
		// Only allow on a single node
		if (nodes.length !== 1) {
			return false
		}
		const node = nodes.at(0)!
		const dueDate = node.attributes['reminder-due-date']
		return dueDate !== undefined
	},

	async exec() {
		return null
	},

	order: 20,
})
