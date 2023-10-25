/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import type { Node } from '@nextcloud/files'

import { FileAction } from '@nextcloud/files'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'

import { DateTimePreset, getDateString, getDateTime, getVerboseDateString } from '../shared/utils'
import { logger } from '../shared/logger'
import { SET_REMINDER_MENU_ID } from './setReminderMenuAction'
import { setReminder } from '../services/reminderService'
import './setReminderSuggestionActions.scss'

interface ReminderOption {
	dateTimePreset: DateTimePreset
	label: string
	ariaLabel: string
	dateString?: string
	action?: () => Promise<void>
}

const laterToday: ReminderOption = {
	dateTimePreset: DateTimePreset.LaterToday,
	label: t('files_reminders', 'Later today'),
	ariaLabel: t('files_reminders', 'Set reminder for later today'),
}

const tomorrow: ReminderOption = {
	dateTimePreset: DateTimePreset.Tomorrow,
	label: t('files_reminders', 'Tomorrow'),
	ariaLabel: t('files_reminders', 'Set reminder for tomorrow'),
}

const thisWeekend: ReminderOption = {
	dateTimePreset: DateTimePreset.ThisWeekend,
	label: t('files_reminders', 'This weekend'),
	ariaLabel: t('files_reminders', 'Set reminder for this weekend'),
}

const nextWeek: ReminderOption = {
	dateTimePreset: DateTimePreset.NextWeek,
	label: t('files_reminders', 'Next week'),
	ariaLabel: t('files_reminders', 'Set reminder for next week'),
}

// Generate the default preset actions
export const actions = [laterToday, tomorrow, thisWeekend, nextWeek].map((option): FileAction|null => {
	const dateTime = getDateTime(option.dateTimePreset)
	if (!dateTime) {
		return null
	}

	return new FileAction({
		id: `set-reminder-${option.dateTimePreset}`,
		displayName: () => `${option.label} - ${getDateString(dateTime)}`,
		title: () => `${option.ariaLabel} – ${getVerboseDateString(dateTime)}`,

		// Empty svg to hide the icon
		iconSvgInline: () => '<svg></svg>',

		enabled: () => true,
		parent: SET_REMINDER_MENU_ID,

		async exec(node: Node) {
			// Can't really happen, but just in case™
			if (!node.fileid) {
				logger.error('Failed to set reminder, missing file id')
				showError(t('files_reminders', 'Failed to set reminder'))
				return null
			}

			// Set the reminder
			try {
				await setReminder(node.fileid, dateTime)
				showSuccess(t('files_reminders', 'Reminder set for "{fileName}"', { fileName: node.basename }))
			} catch (error) {
				logger.error('Failed to set reminder', { error })
				showError(t('files_reminders', 'Failed to set reminder'))
			}
			// Silent success as we display our own notification
			return null
		},

		order: 21,
	})
}).filter(Boolean) as FileAction[]
