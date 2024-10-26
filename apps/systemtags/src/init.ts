/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { registerDavProperty, registerFileAction } from '@nextcloud/files'
import { action as inlineSystemTagsAction } from './files_actions/inlineSystemTagsAction.js'
import { action as openInFilesAction } from './files_actions/openInFilesAction.js'
import { registerSystemTagsView } from './files_views/systemtagsView.js'

registerDavProperty('nc:system-tags')
registerFileAction(inlineSystemTagsAction)
registerFileAction(openInFilesAction)

registerSystemTagsView()
