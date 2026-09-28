/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ShareAttribute } from './sharing.d.ts'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { Folder, getNavigation } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import registerFileDropView from './files_views/publicFileDrop.ts'
import registerPublicShareView from './files_views/publicShare.ts'
import registerPublicFileShareView from './files_views/publicFileShare.ts'
import RouterService from '../../files/src/services/RouterService.ts'
import router from './router/index.ts'
import logger from './services/logger.ts'

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

// When the file list is loaded we need to apply the "userconfig" setup on the share
subscribe('files:list:updated', loadShareConfig)

/**
 * Event handler to load the view config for the current share.
 * This is done on the `files:list:updated` event to ensure the list and especially the config store was correctly initialized.
 *
 * @param context The event context
 * @param context.folder The current folder
 */
function loadShareConfig({ folder }: { folder: Folder }) {
	// Only setup config once
	unsubscribe('files:list:updated', loadShareConfig)

	// Share attributes (the same) are set on all folders of a share
	if (folder.attributes['share-attributes']) {
		const shareAttributes = JSON.parse(folder.attributes['share-attributes'] || '[]') as Array<ShareAttribute>
		const gridViewAttribute = shareAttributes.find(({ scope, key }: ShareAttribute) => scope === 'config' && key === 'grid_view')
		if (gridViewAttribute !== undefined) {
			logger.debug('Loading share attributes', { gridViewAttribute })
			emit('files:config:updated', { key: 'grid_view', value: gridViewAttribute.value === true })
		}
	}
}
