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
