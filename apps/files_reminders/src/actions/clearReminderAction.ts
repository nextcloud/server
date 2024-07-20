/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import { FileAction, type Node } from '@nextcloud/files'
import { emit } from '@nextcloud/event-bus'
import { translate as t } from '@nextcloud/l10n'

import AlarmOffSvg from '@mdi/svg/svg/alarm-off.svg?raw'

import { clearReminder } from '../services/reminderService.ts'
import { getVerboseDateString } from '../shared/utils.ts'

export const action = new FileAction({
	id: 'clear-reminder',

	displayName: () => t('files_reminders', 'Clear reminder'),

	title: (nodes: Node[]) => {
		const node = nodes.at(0)!
		const dueDate = new Date(node.attributes['reminder-due-date'])
		return `${t('files_reminders', 'Clear reminder')} – ${getVerboseDateString(dueDate)}`
	},

	iconSvgInline: () => AlarmOffSvg,

	enabled: (nodes: Node[]) => {
		// Only allow on a single node
		if (nodes.length !== 1) {
			return false
		}
		const node = nodes.at(0)!
		const dueDate = node.attributes['reminder-due-date']
		return Boolean(dueDate)
	},

	async exec(node: Node) {
		if (node.fileid) {
			try {
				await clearReminder(node.fileid)
				Vue.set(node.attributes, 'reminder-due-date', '')
				emit('files:node:updated', node)
				return true
			} catch (error) {
				return false
			}
		}
		return null
	},

	order: 19,
})
