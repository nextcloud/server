/*!
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { addNewFileMenuEntry, registerFileAction } from '@nextcloud/files'
import { registerDavProperty } from '@nextcloud/files/dav'
import { action as acceptShareAction } from './files_actions/acceptShareAction.ts'
import { action as openInFilesAction } from './files_actions/openInFilesAction.ts'
import { action as rejectShareAction } from './files_actions/rejectShareAction.ts'
import { action as restoreShareAction } from './files_actions/restoreShareAction.ts'
import { action as sharingStatusAction } from './files_actions/sharingStatusAction.ts'
import { registerAccountFilter } from './files_filters/AccountFilter.ts'
import registerNoteToRecipient from './files_headers/noteToRecipient.ts'
import { entry as newFileRequest } from './files_newMenu/newFileRequest.ts'
import registerSharingViews from './files_views/shares.ts'

registerSharingViews()

addNewFileMenuEntry(newFileRequest)

registerDavProperty('nc:note', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:sharees', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:hide-download', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:share-attributes', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('oc:share-types', { oc: 'http://owncloud.org/ns' })
registerDavProperty('ocs:share-permissions', { ocs: 'http://open-collaboration-services.org/ns' })

registerFileAction(acceptShareAction)
registerFileAction(openInFilesAction)
registerFileAction(rejectShareAction)
registerFileAction(restoreShareAction)
registerFileAction(sharingStatusAction)

registerAccountFilter()

// Add "note to recipient" message
registerNoteToRecipient()
