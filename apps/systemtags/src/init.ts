/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { registerDavProperty, registerFileAction } from '@nextcloud/files'
import { registerHotkeys } from './services/HotKeysService'
import { registerSystemTagsView } from './files_views/systemtagsView'

import { action as bulkSystemTagsAction } from './files_actions/bulkSystemTagsAction'
import { action as inlineSystemTagsAction } from './files_actions/inlineSystemTagsAction'
import { action as openInFilesAction } from './files_actions/openInFilesAction'

registerDavProperty('nc:system-tags')
registerFileAction(bulkSystemTagsAction)
registerFileAction(inlineSystemTagsAction)
registerFileAction(openInFilesAction)

registerSystemTagsView()

document.addEventListener('DOMContentLoaded', () => {
	registerHotkeys()
})
