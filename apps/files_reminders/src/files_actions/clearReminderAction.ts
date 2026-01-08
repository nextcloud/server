/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import AlarmOffSvg from '@mdi/svg/svg/alarm-off.svg?raw'
import { emit } from '@nextcloud/event-bus'
import { FileAction } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { clearReminder } from '../services/reminderService.ts'
import { getVerboseDateString } from '../shared/utils.ts'

export const action = new FileAction({
	id: 'clear-reminder',

	displayName: () => t('files_reminders', 'Clear reminder'),

	title: ({ nodes }) => {
		const node = nodes.at(0)!
		const dueDate = new Date(node.attributes['reminder-due-date'])
		return `${t('files_reminders', 'Clear reminder')} – ${getVerboseDateString(dueDate)}`
	},

	iconSvgInline: () => AlarmOffSvg,

	enabled: ({ nodes }) => {
		// Only allow on a single node
		if (nodes.length !== 1) {
			return false
		}
		const node = nodes.at(0)!
		const dueDate = node.attributes['reminder-due-date']
		return Boolean(dueDate)
	},

	async exec({ nodes }) {
		const node = nodes.at(0)!
		if (node.fileid) {
			try {
				await clearReminder(node.fileid)
				node.attributes['reminder-due-date'] = ''
				emit('files:node:updated', node)
				return true
			} catch {
				return false
			}
		}
		return null
	},

	order: 19,
})
