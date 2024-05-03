/**
 * @copyright 2024 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import type { Node } from '@nextcloud/files'

import { translate as t } from '@nextcloud/l10n'
import { dirname } from '@nextcloud/paths'

export enum RestoreParents {
	Confirm = 'Confirm',
	Skip = 'Skip',
	Cancel = 'Cancel',
}

export const sortByDeletionTime = (a: Node, b: Node) => {
	const deletionTimeA = a.attributes?.['trashbin-deletion-time'] || a?.mtime || 0
	const deletionTimeB = b.attributes?.['trashbin-deletion-time'] || b?.mtime || 0
	return deletionTimeB - deletionTimeA
}

/**
 * @param node the node
 * @param fullPath if true will return the full path
 */
export const parseOriginalLocation = (node: Node, fullPath: boolean = false): string => {
	const path = node.attributes?.['trashbin-original-location'] !== undefined
		? String(node.attributes?.['trashbin-original-location']).replace(/^\//, '')
		: null
	if (!path) {
		return t('files_trashbin', 'Unknown')
	}
	if (fullPath) {
		return path
	}
	const dir = dirname(path)
	if (dir === path) { // Node is in root folder
		return t('files_trashbin', 'All files')
	}
	return dir
}
