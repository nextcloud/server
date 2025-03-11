/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { trashbinView } from './files_views/trashbinView.ts'
import './trashbin.scss'

import { translate as t } from '@nextcloud/l10n'
import { View, getNavigation, registerFileListAction } from '@nextcloud/files'
import DeleteSvg from '@mdi/svg/svg/delete.svg?raw'

import { getContents } from './services/trashbin'
import { columns } from './columns.ts'

// Register restore action
import './actions/restoreAction'

import { emptyTrashAction } from './fileListActions/emptyTrashAction.ts'

const Navigation = getNavigation()
Navigation.register(trashbinView)

registerFileListAction(emptyTrashAction)
