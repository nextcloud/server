/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

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
Navigation.register(new View({
	id: 'trashbin',
	name: t('files_trashbin', 'Deleted files'),
	caption: t('files_trashbin', 'List of files that have been deleted.'),

	emptyTitle: t('files_trashbin', 'No deleted files'),
	emptyCaption: t('files_trashbin', 'Files and folders you have deleted will show up here'),

	icon: DeleteSvg,
	order: 50,
	sticky: true,

	defaultSortKey: 'deleted',

	columns,

	getContents,
}))

registerFileListAction(emptyTrashAction)
