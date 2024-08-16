/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { FileAction, type Node } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'

import AlarmSvg from '@mdi/svg/svg/alarm.svg?raw'

import { pickCustomDate } from '../services/customPicker.ts'
import { getVerboseDateString } from '../shared/utils.ts'

export const action = new FileAction({
	id: 'reminder-status',

	inline: () => true,

	displayName: () => '',

	title: (nodes: Node[]) => {
		const node = nodes.at(0)!
		const dueDate = new Date(node.attributes['reminder-due-date'])
		return `${t('files_reminders', 'Reminder set')} – ${getVerboseDateString(dueDate)}`
	},

	iconSvgInline: () => AlarmSvg,

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
		pickCustomDate(node)
		return null
	},

	order: -15,
})
