/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { addNewFileMenuEntry, registerDavProperty } from '@nextcloud/files'
import { registerAccountFilter } from './filters/AccountFilter'
import { entry as newFileRequest } from './new/newFileRequest.ts'

import registerNoteToRecipient from './files_headers/noteToRecipient'
import registerSharingViews from './views/shares'

import './actions/acceptShareAction'
import './actions/openInFilesAction'
import './actions/rejectShareAction'
import './actions/restoreShareAction'
import './actions/sharingStatusAction'

registerSharingViews()

addNewFileMenuEntry(newFileRequest)

registerDavProperty('nc:note', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:sharees', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:share-attributes', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('oc:share-types', { oc: 'http://owncloud.org/ns' })
registerDavProperty('ocs:share-permissions', { ocs: 'http://open-collaboration-services.org/ns' })

registerAccountFilter()

// Add "note to recipient" message
registerNoteToRecipient()
