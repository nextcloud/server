/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
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

import { registerDavProperty, registerFileAction } from '@nextcloud/files'
import { action as statusAction } from './actions/reminderStatusAction'
import { action as clearAction } from './actions/clearReminderAction'
import { action as menuAction } from './actions/setReminderMenuAction'
import { actions as suggestionActions } from './actions/setReminderSuggestionActions'
import { action as customAction } from './actions/setReminderCustomAction'

registerDavProperty('nc:reminder-due-date', { nc: 'http://nextcloud.org/ns' })

registerFileAction(statusAction)
registerFileAction(clearAction)
registerFileAction(menuAction)
registerFileAction(customAction)
suggestionActions.forEach((action) => registerFileAction(action))
