/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getNavigation, registerFileAction, registerFileListAction } from '@nextcloud/files'
import { restoreAction } from './files_actions/restoreAction.ts'
import { emptyTrashAction } from './files_listActions/emptyTrashAction.ts'
import { trashbinView } from './files_views/trashbinView.ts'

import './trashbin.scss'

const Navigation = getNavigation()
Navigation.register(trashbinView)

registerFileListAction(emptyTrashAction)
registerFileAction(restoreAction)
