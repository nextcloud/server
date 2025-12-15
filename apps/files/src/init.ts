/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { addNewFileMenuEntry, registerFileAction } from '@nextcloud/files'
import { registerDavProperty } from '@nextcloud/files/dav'
import { isPublicShare } from '@nextcloud/sharing/public'
import { registerConvertActions } from './actions/convertAction.ts'
import { action as deleteAction } from './actions/deleteAction.ts'
import { action as downloadAction } from './actions/downloadAction.ts'
import { action as favoriteAction } from './actions/favoriteAction.ts'
import { action as moveOrCopyAction } from './actions/moveOrCopyAction.ts'
import { action as openFolderAction } from './actions/openFolderAction.ts'
import { action as openInFilesAction } from './actions/openInFilesAction.ts'
import { action as editLocallyAction } from './actions/openLocallyAction.ts'
import { action as renameAction } from './actions/renameAction.ts'
import { action as sidebarAction } from './actions/sidebarAction.ts'
import { action as viewInFolderAction } from './actions/viewInFolderAction.ts'
import { registerFilenameFilter } from './filters/FilenameFilter.ts'
import { registerHiddenFilesFilter } from './filters/HiddenFilesFilter.ts'
import { registerModifiedFilter } from './filters/ModifiedFilter.ts'
import { registerFilterToSearchToggle } from './filters/SearchFilter.ts'
import { registerTypeFilter } from './filters/TypeFilter.ts'
import { entry as newFolderEntry } from './newMenu/newFolder.ts'
import { registerTemplateEntries } from './newMenu/newFromTemplate.ts'
import { entry as newTemplatesFolder } from './newMenu/newTemplatesFolder.ts'
import { initLivePhotos } from './services/LivePhotos.ts'
import registerPreviewServiceWorker from './services/ServiceWorker.js'
import { registerFavoritesView } from './views/favorites.ts'
import { registerFilesView } from './views/files.ts'
import { registerFolderTreeView } from './views/folderTree.ts'
import { registerPersonalFilesView } from './views/personal-files.ts'
import registerRecentView from './views/recent.ts'
import { registerSearchView } from './views/search.ts'

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
	registerPersonalFilesView()
	registerRecentView()
	registerSearchView()
	registerFolderTreeView()
}

// Register file list filters
registerHiddenFilesFilter()
registerTypeFilter()
registerModifiedFilter()
registerFilenameFilter()
registerFilterToSearchToggle()

// Register preview service worker
registerPreviewServiceWorker()

registerDavProperty('nc:hidden', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:is-mount-root', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:metadata-blurhash', { nc: 'http://nextcloud.org/ns' })

initLivePhotos()
