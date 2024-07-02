/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getNavigation, registerDavProperty } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import registerNoteToRecipient from './files_headers/noteToRecipient.ts'
import registerFileDropView from './files_views/publicFileDrop.ts'
import registerPublicShareView from './files_views/publicShare.ts'
import registerPublicFileShareView from './files_views/publicFileShare.ts'
import RouterService from '../../files/src/services/RouterService'
import router from './router'

registerFileDropView()
registerPublicShareView()
registerPublicFileShareView()

registerDavProperty('nc:share-attributes', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('oc:share-types', { oc: 'http://owncloud.org/ns' })
registerDavProperty('ocs:share-permissions', { ocs: 'http://open-collaboration-services.org/ns' })

// Add "note to recipient" message
registerNoteToRecipient()

// Get the current view from state and set it active
const view = loadState<string>('files_sharing', 'view')
const navigation = getNavigation()
navigation.setActive(navigation.views.find(({ id }) => id === view) ?? null)

// Force our own router
window.OCP.Files = window.OCP.Files ?? {}
window.OCP.Files.Router = new RouterService(router)
