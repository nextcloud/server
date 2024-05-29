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
import { entry as newFolderEntry } from './newMenu/newFolder.ts'
import { entry as newTemplatesFolder } from './newMenu/newTemplatesFolder.ts'
import { registerTemplateEntries } from './newMenu/newFromTemplate.ts'

import registerFavoritesView from './views/favorites'
import registerRecentView from './views/recent'
import registerPersonalFilesView from './views/personal-files'
import registerFilesView from './views/files'
import registerPreviewServiceWorker from './services/ServiceWorker.js'


import { initLivePhotos } from './services/LivePhotos'

// Register file actions
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

// Register files views
registerFavoritesView()
registerFilesView()
registerRecentView()
registerPersonalFilesView()

// Register preview service worker
registerPreviewServiceWorker()

registerDavProperty('nc:hidden', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:is-mount-root', { nc: 'http://nextcloud.org/ns' })

initLivePhotos()
