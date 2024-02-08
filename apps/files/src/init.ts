/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
import { entry as newFolderEntry } from './newMenu/newFolder'

import registerFavoritesView from './views/favorites'
import registerRecentView from './views/recent'
import registerFilesView from './views/files'
import registerPreviewServiceWorker from './services/ServiceWorker.js'

import './init-templates'

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

// Register files views
registerFavoritesView()
registerFilesView()
registerRecentView()

// Register preview service worker
registerPreviewServiceWorker()

registerDavProperty('nc:hidden', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:is-mount-root', { nc: 'http://nextcloud.org/ns' })

initLivePhotos()
