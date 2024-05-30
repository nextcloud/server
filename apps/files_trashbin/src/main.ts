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

import './trashbin.scss'

import { translate as t } from '@nextcloud/l10n'
import DeleteSvg from '@mdi/svg/svg/delete.svg?raw'

import { getContents } from './services/trashbin'
import { columns } from './columns.ts'

// Register restore action
import './actions/restoreAction'
import { View, getNavigation } from '@nextcloud/files'

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
