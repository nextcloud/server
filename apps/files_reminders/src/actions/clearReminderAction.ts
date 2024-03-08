/**
 * @copyright 2024 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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

	displayName: () => t('files', 'Clear reminder'),

	title: (nodes: Node[]) => {
		const node = nodes.at(0)!
		const dueDate = new Date(node.attributes['reminder-due-date'])
		return `${t('files', 'Clear reminder')} – ${getVerboseDateString(dueDate)}`
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
