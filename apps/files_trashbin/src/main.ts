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
import type NavigationService from '../../files/src/services/Navigation'
import type { Navigation } from '../../files/src/services/Navigation'

import { translate as t, translate } from '@nextcloud/l10n'
import DeleteSvg from '@mdi/svg/svg/delete.svg?raw'
import moment from '@nextcloud/moment'

import { getContents } from './services/trashbin'

// Register restore action
import './actions/restoreAction'

const Navigation = window.OCP.Files.Navigation as NavigationService
Navigation.register({
	id: 'trashbin',
	name: t('files_trashbin', 'Deleted files'),
	caption: t('files_trashbin', 'List of files that have been deleted.'),

	emptyTitle: t('files_trashbin', 'No deleted files'),
	emptyCaption: t('files_trashbin', 'Files and folders you have deleted will show up here'),

	icon: DeleteSvg,
	order: 50,
	sticky: true,

	defaultSortKey: 'deleted',

	columns: [
		{
			id: 'deleted',
			title: t('files_trashbin', 'Deleted'),
			render(node) {
				const deletionTime = node.attributes?.['trashbin-deletion-time']
				const span = document.createElement('span')
				if (deletionTime) {
					span.title = moment.unix(deletionTime).format('LLL')
					span.textContent = moment.unix(deletionTime).fromNow()
					return span
				}

				// Unknown deletion time
				span.textContent = translate('files_trashbin', 'A long time ago')
				return span
			},
			sort(nodeA, nodeB) {
				const deletionTimeA = nodeA.attributes?.['trashbin-deletion-time'] || nodeA?.mtime || 0
				const deletionTimeB = nodeB.attributes?.['trashbin-deletion-time'] || nodeB?.mtime || 0
				return deletionTimeB - deletionTimeA
			},
		},
	],

	getContents,
} as Navigation)
