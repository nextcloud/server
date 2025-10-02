/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { addNewFileMenuEntry } from '@nextcloud/files'
import { registerDavProperty } from '@nextcloud/files/dav'
import { registerAccountFilter } from './files_filters/AccountFilter.ts'
import registerNoteToRecipient from './files_headers/noteToRecipient.ts'
import { entry as newFileRequest } from './files_newMenu/newFileRequest.ts'
import registerSharingViews from './files_views/shares.ts'

import './files_actions/acceptShareAction.ts'
import './files_actions/openInFilesAction.ts'
import './files_actions/rejectShareAction.ts'
import './files_actions/restoreShareAction.ts'
import './files_actions/sharingStatusAction.ts'

registerSharingViews()

addNewFileMenuEntry(newFileRequest)

registerDavProperty('nc:note', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:sharees', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:hide-download', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:share-attributes', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('oc:share-types', { oc: 'http://owncloud.org/ns' })
registerDavProperty('ocs:share-permissions', { ocs: 'http://open-collaboration-services.org/ns' })

registerAccountFilter()

// Add "note to recipient" message
registerNoteToRecipient()
