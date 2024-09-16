/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import type { Node, View } from '@nextcloud/files'

import { FileAction } from '@nextcloud/files'
import { emit } from '@nextcloud/event-bus'
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
	verboseDateString?: string
	action?: () => Promise<void>
}

const laterToday: ReminderOption = {
	dateTimePreset: DateTimePreset.LaterToday,
	label: t('files_reminders', 'Later today'),
	ariaLabel: t('files_reminders', 'Set reminder for later today'),
	dateString: '',
	verboseDateString: '',
}

const tomorrow: ReminderOption = {
	dateTimePreset: DateTimePreset.Tomorrow,
	label: t('files_reminders', 'Tomorrow'),
	ariaLabel: t('files_reminders', 'Set reminder for tomorrow'),
	dateString: '',
	verboseDateString: '',
}

const thisWeekend: ReminderOption = {
	dateTimePreset: DateTimePreset.ThisWeekend,
	label: t('files_reminders', 'This weekend'),
	ariaLabel: t('files_reminders', 'Set reminder for this weekend'),
	dateString: '',
	verboseDateString: '',
}

const nextWeek: ReminderOption = {
	dateTimePreset: DateTimePreset.NextWeek,
	label: t('files_reminders', 'Next week'),
	ariaLabel: t('files_reminders', 'Set reminder for next week'),
	dateString: '',
	verboseDateString: '',
}

/**
 * Generate a file action for the given option
 *
 * @param option The option to generate the action for
 * @return The file action or null if the option should not be shown
 */
const generateFileAction = (option: ReminderOption): FileAction|null => {

	return new FileAction({
		id: `set-reminder-${option.dateTimePreset}`,
		displayName: () => `${option.label} – ${option.dateString}`,
		title: () => `${option.ariaLabel} – ${option.verboseDateString}`,

		// Empty svg to hide the icon
		iconSvgInline: () => '<svg></svg>',

		enabled: (_nodes: Node[], view: View) => {
			if (view.id === 'trashbin') {
				return false
			}
			return Boolean(getDateTime(option.dateTimePreset))
		},

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
				const dateTime = getDateTime(option.dateTimePreset)!
				await setReminder(node.fileid, dateTime)
				Vue.set(node.attributes, 'reminder-due-date', dateTime.toISOString())
				emit('files:node:updated', node)
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
}

[laterToday, tomorrow, thisWeekend, nextWeek].forEach((option) => {
	// Generate the initial date string
	const dateTime = getDateTime(option.dateTimePreset)
	if (!dateTime) {
		return
	}
	option.dateString = getDateString(dateTime)
	option.verboseDateString = getVerboseDateString(dateTime)

	// Update the date string every 30 minutes
	setInterval(() => {
		const dateTime = getDateTime(option.dateTimePreset)
		if (!dateTime) {
			return
		}

		// update the submenu remind options strings
		option.dateString = getDateString(dateTime)
		option.verboseDateString = getVerboseDateString(dateTime)
	}, 1000 * 30 * 60)
})

// Generate the default preset actions
export const actions = [laterToday, tomorrow, thisWeekend, nextWeek]
	.map(generateFileAction) as FileAction[]
