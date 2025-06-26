/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { addNewFileMenuEntry, registerFileAction } from '@nextcloud/files'
import { registerDavProperty } from '@nextcloud/files/dav'
import { isPublicShare } from '@nextcloud/sharing/public'

import { action as deleteAction } from './actions/deleteAction'
import { action as downloadAction } from './actions/downloadAction'
import { action as editLocallyAction } from './actions/openLocallyAction.ts'
import { action as favoriteAction } from './actions/favoriteAction'
import { action as moveOrCopyAction } from './actions/moveOrCopyAction'
import { action as openFolderAction } from './actions/openFolderAction'
import { action as openInFilesAction } from './actions/openInFilesAction'
import { action as renameAction } from './actions/renameAction'
import { action as sidebarAction } from './actions/sidebarAction'
import { action as viewInFolderAction } from './actions/viewInFolderAction'
import { registerConvertActions } from './actions/convertAction.ts'

import { registerHiddenFilesFilter } from './filters/HiddenFilesFilter.ts'
import { registerTypeFilter } from './filters/TypeFilter.ts'
import { registerModifiedFilter } from './filters/ModifiedFilter.ts'
import { registerFilenameFilter } from './filters/FilenameFilter.ts'

import { entry as newFolderEntry } from './newMenu/newFolder.ts'
import { entry as newTemplatesFolder } from './newMenu/newTemplatesFolder.ts'
import { registerTemplateEntries } from './newMenu/newFromTemplate.ts'

import { registerFavoritesView } from './views/favorites.ts'
import { registerFilesView } from './views/files'
import { registerFolderTreeView } from './views/folderTree.ts'
import { registerHomeView } from './views/home'
import { registerPersonalFilesView } from './views/personal-files'
import { registerRecentView } from './views/recent'
import { registerSearchView } from './views/search.ts'

import { registerLivePhotosService } from './services/LivePhotos'
import { registerPreviewServiceWorker } from './services/ServiceWorker.js'

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
	registerFolderTreeView()
	registerHomeView()
	registerPersonalFilesView()
	registerRecentView()
	registerSearchView()
}

// Register file list filters
registerHiddenFilesFilter()
registerTypeFilter()
registerModifiedFilter()
registerFilenameFilter()

// Register various services
registerPreviewServiceWorker()
registerLivePhotosService()

registerDavProperty('nc:hidden', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:is-mount-root', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:metadata-blurhash', { nc: 'http://nextcloud.org/ns' })
