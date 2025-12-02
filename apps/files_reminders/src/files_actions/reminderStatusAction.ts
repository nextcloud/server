/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'

import AlarmSvg from '@mdi/svg/svg/alarm.svg?raw'
import { FileAction } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { pickCustomDate } from '../services/customPicker.ts'
import { getVerboseDateString } from '../shared/utils.ts'

export const action = new FileAction({
	id: 'reminder-status',

	inline: () => true,

	displayName: () => '',

	title: (nodes: INode[]) => {
		const node = nodes.at(0)!
		const dueDate = new Date(node.attributes['reminder-due-date'])
		return `${t('files_reminders', 'Reminder set')} – ${getVerboseDateString(dueDate)}`
	},

	iconSvgInline: () => AlarmSvg,

	enabled: (nodes: INode[]) => {
		// Only allow on a single node
		if (nodes.length !== 1) {
			return false
		}
		const node = nodes.at(0)!
		const dueDate = node.attributes['reminder-due-date']
		return Boolean(dueDate)
	},

	async exec(node: INode) {
		await pickCustomDate(node)
		return null
	},

	order: -15,
})
