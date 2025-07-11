/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { View } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { deleted, deletedBy, originalLocation } from './columns.ts'
import { getContents } from '../services/trashbin.ts'

import svgDelete from '@mdi/svg/svg/delete.svg?raw'

export const TRASHBIN_VIEW_ID = 'trashbin'

export const trashbinView = new View({
	id: TRASHBIN_VIEW_ID,
	name: t('files_trashbin', 'Deleted files'),
	caption: t('files_trashbin', 'List of files that have been deleted.'),

	emptyTitle: t('files_trashbin', 'No deleted files'),
	emptyCaption: t('files_trashbin', 'Files and folders you have deleted will show up here'),

	icon: svgDelete,
	order: 50,
	sticky: true,

	defaultSortKey: 'deleted',

	columns: [
		originalLocation,
		deletedBy,
		deleted,
	],

	getContents,
})
