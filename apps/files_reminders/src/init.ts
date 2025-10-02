/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { registerDavProperty, registerFileAction } from '@nextcloud/files'
import { action as clearAction } from './actions/clearReminderAction.ts'
import { action as statusAction } from './actions/reminderStatusAction.ts'
import { action as customAction } from './actions/setReminderCustomAction.ts'
import { action as menuAction } from './actions/setReminderMenuAction.ts'
import { actions as suggestionActions } from './actions/setReminderSuggestionActions.ts'

registerDavProperty('nc:reminder-due-date', { nc: 'http://nextcloud.org/ns' })

registerFileAction(statusAction)
registerFileAction(clearAction)
registerFileAction(menuAction)
registerFileAction(customAction)
suggestionActions.forEach((action) => registerFileAction(action))
