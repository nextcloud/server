/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import CalendarClockSvg from '@mdi/svg/svg/calendar-clock.svg?raw'
import { FileAction } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { pickCustomDate } from '../services/customPicker.ts'
import { SET_REMINDER_MENU_ID } from './setReminderMenuAction.ts'

export const action = new FileAction({
	id: 'set-reminder-custom',
	displayName: () => t('files_reminders', 'Custom reminder'),
	title: () => t('files_reminders', 'Reminder at custom date & time'),
	iconSvgInline: () => CalendarClockSvg,

	enabled: ({ nodes, view }) => {
		if (view.id === 'trashbin') {
			return false
		}
		// Only allow on a single INode
		if (nodes.length !== 1) {
			return false
		}
		const node = nodes.at(0)!
		const dueDate = node.attributes['reminder-due-date']
		return dueDate !== undefined
	},

	parent: SET_REMINDER_MENU_ID,

	async exec({ nodes }) {
		const node = nodes.at(0)!
		pickCustomDate(node)
		return null
	},

	// After presets
	order: 22,
})
