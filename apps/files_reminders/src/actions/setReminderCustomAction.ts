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
import { FileAction, Node } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import CalendarClockSvg from '@mdi/svg/svg/calendar-clock.svg?raw'

import { SET_REMINDER_MENU_ID } from './setReminderMenuAction'
import { pickCustomDate } from '../services/customPicker'

export const action = new FileAction({
	id: 'set-reminder-custom',
	displayName: () => t('files_reminders', 'Set custom reminder'),
	title: () => t('files_reminders', 'Set reminder at custom date & time'),
	iconSvgInline: () => CalendarClockSvg,

	enabled: () => true,
	parent: SET_REMINDER_MENU_ID,

	async exec(file: Node) {
		pickCustomDate(file)
		return null
	},

	// After presets
	order: 22,
})
