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

import type NavigationService from '../../files/src/services/Navigation.ts'
import type { Navigation } from '../../files/src/services/Navigation.ts'

import { translate as t } from '@nextcloud/l10n'
import DeleteSvg from '@mdi/svg/svg/delete.svg?raw'
import moment from '@nextcloud/moment'

import { getContents } from './services/trashbin'

// Register restore action
import './actions/restoreAction'
import type { Node } from '@nextcloud/files'
import { dirname, joinPaths } from '@nextcloud/paths'

const parseOriginalLocation = (node: Node): string => {
	const path = node.attributes?.['trashbin-original-location'] !== undefined ? String(node.attributes?.['trashbin-original-location']) : null
	if (!path) {
		return t('files_trashbin', 'Unknown')
	}
	const dir = dirname(path)
	if (dir === path) { // Node is in root folder
		return t('files_trashbin', 'All files')
	}
	return joinPaths(t('files_trashbin', 'All files'), dir)
}

const Navigation = window.OCP.Files.Navigation as NavigationService
Navigation.register({
	id: 'trashbin',
	name: t('files_trashbin', 'Deleted files'),
	caption: t('files_trashbin', 'List of files that have been deleted.'),

	icon: DeleteSvg,
	order: 50,
	sticky: true,

	defaultSortKey: 'deleted',

	columns: [
		{
			id: 'original-location',
			title: t('files_trashbin', 'Original location'),
			render(node) {
				const originalLocation = parseOriginalLocation(node)
				const span = document.createElement('span')
				span.title = originalLocation
				span.textContent = originalLocation
				return span
			},
			sort(nodeA, nodeB) {
				const locationA = parseOriginalLocation(nodeA)
				const locationB = parseOriginalLocation(nodeB)
				return locationA.localeCompare(locationB)
			},
		},
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
				span.textContent = t('files_trashbin', 'A long time ago')
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
