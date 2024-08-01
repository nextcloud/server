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

	enabled: (_nodes: Node[], view: View) => {
		return view.id !== 'trashbin'
	},

	async exec() {
		return null
	},

	order: 20,
})
