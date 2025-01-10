/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { addNewFileMenuEntry, registerDavProperty, registerFileAction } from '@nextcloud/files'

import { action as deleteAction } from './actions/deleteAction'
import { action as downloadAction } from './actions/downloadAction'
import { action as editLocallyAction } from './actions/editLocallyAction'
import { action as favoriteAction } from './actions/favoriteAction'
import { action as moveOrCopyAction } from './actions/moveOrCopyAction'
import { action as openFolderAction } from './actions/openFolderAction'
import { action as openInFilesAction } from './actions/openInFilesAction'
import { action as renameAction } from './actions/renameAction'
import { action as sidebarAction } from './actions/sidebarAction'
import { action as viewInFolderAction } from './actions/viewInFolderAction'

import { registerHiddenFilesFilter } from './filters/HiddenFilesFilter.ts'
import { registerTypeFilter } from './filters/TypeFilter.ts'
import { registerModifiedFilter } from './filters/ModifiedFilter.ts'

import { entry as newFolderEntry } from './newMenu/newFolder.ts'
import { entry as newTemplatesFolder } from './newMenu/newTemplatesFolder.ts'
import { registerTemplateEntries } from './newMenu/newFromTemplate.ts'

import { registerFavoritesView } from './views/favorites.ts'
import registerRecentView from './views/recent'
import registerPersonalFilesView from './views/personal-files'
import registerFilesView from './views/files'
import { registerFolderTreeView } from './views/folderTree.ts'
import registerPreviewServiceWorker from './services/ServiceWorker.js'

import { initLivePhotos } from './services/LivePhotos'
import { isPublicShare } from '@nextcloud/sharing/public'
import { registerConvertActions } from './actions/convertAction.ts'

// Register file actions
registerConvertActions()
registerFileAction(deleteAction)
registerFileAction(downloadAction)
registerFileAction(editLocallyAction)
registerFileAction(favoriteAction)
registerFileAction(moveOrCopyAction)
registerFileAction(openFolderAction)
registerFileAction(openInFilesAction)
registerFileAction(renameAction)
registerFileAction(sidebarAction)
registerFileAction(viewInFolderAction)

// Register new menu entry
addNewFileMenuEntry(newFolderEntry)
addNewFileMenuEntry(newTemplatesFolder)
registerTemplateEntries()

// Register files views when not on public share
if (isPublicShare() === false) {
	registerFavoritesView()
	registerFilesView()
	registerRecentView()
	registerPersonalFilesView()
	registerFolderTreeView()
}

// Register file list filters
registerHiddenFilesFilter()
registerTypeFilter()
registerModifiedFilter()

// Register preview service worker
registerPreviewServiceWorker()

registerDavProperty('nc:hidden', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:is-mount-root', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:metadata-blurhash', { nc: 'http://nextcloud.org/ns' })

initLivePhotos()
