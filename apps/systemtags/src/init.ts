/*!
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { registerFileAction } from '@nextcloud/files'
import { registerDavProperty } from '@nextcloud/files/dav'
import { action as bulkSystemTagsAction } from './files_actions/bulkSystemTagsAction.ts'
import { registerFileSidebarAction } from './files_actions/filesSidebarAction.ts'
import { action as inlineSystemTagsAction } from './files_actions/inlineSystemTagsAction.ts'
import { action as openInFilesAction } from './files_actions/openInFilesAction.ts'
import { registerSystemTagsView } from './files_views/systemtagsView.ts'

registerDavProperty('nc:system-tags')
registerFileAction(bulkSystemTagsAction)
registerFileAction(inlineSystemTagsAction)
registerFileAction(openInFilesAction)

registerSystemTagsView()
registerFileSidebarAction()
