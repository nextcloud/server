/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { registerFileAction } from '@nextcloud/files'
import { registerDavProperty } from '@nextcloud/files/dav'
import { action as clearAction } from './files_actions/clearReminderAction.ts'
import { action as statusAction } from './files_actions/reminderStatusAction.ts'
import { action as customAction } from './files_actions/setReminderCustomAction.ts'
import { action as menuAction } from './files_actions/setReminderMenuAction.ts'
import { actions as suggestionActions } from './files_actions/setReminderSuggestionActions.ts'

registerDavProperty('nc:reminder-due-date', { nc: 'http://nextcloud.org/ns' })

registerFileAction(statusAction)
registerFileAction(clearAction)
registerFileAction(menuAction)
registerFileAction(customAction)
suggestionActions.forEach((action) => registerFileAction(action))
