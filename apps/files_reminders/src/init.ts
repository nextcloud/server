/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
