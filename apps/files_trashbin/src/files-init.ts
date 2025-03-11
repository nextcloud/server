/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getNavigation, registerFileListAction } from '@nextcloud/files'
import { emptyTrashAction } from './files_actions/emptyTrashAction.ts'
import { trashbinView } from './files_views/trashbinView.ts'

import './trashbin.scss'

const Navigation = getNavigation()
Navigation.register(trashbinView)

registerFileListAction(emptyTrashAction)
