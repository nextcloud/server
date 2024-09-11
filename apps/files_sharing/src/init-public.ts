/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getNavigation } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import registerFileDropView from './files_views/publicFileDrop.ts'
import registerPublicShareView from './files_views/publicShare.ts'
import registerPublicFileShareView from './files_views/publicFileShare.ts'
import RouterService from '../../files/src/services/RouterService'
import router from './router'

registerFileDropView()
registerPublicShareView()
registerPublicFileShareView()

// Get the current view from state and set it active
const view = loadState<string>('files_sharing', 'view')
const navigation = getNavigation()
navigation.setActive(navigation.views.find(({ id }) => id === view) ?? null)

// Force our own router
window.OCP.Files = window.OCP.Files ?? {}
window.OCP.Files.Router = new RouterService(router)

// If this is a single file share, so set the fileid as active in the URL
const fileId = loadState<number|null>('files_sharing', 'fileId', null)
const token = loadState<string>('files_sharing', 'sharingToken')
if (fileId !== null) {
	window.OCP.Files.Router.goToRoute(
		'filelist',
		{ ...window.OCP.Files.Router.params, token, fileid: String(fileId) },
		{ ...window.OCP.Files.Router.query, openfile: 'true' },
	)
}
