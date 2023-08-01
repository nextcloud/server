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
import { FileType, Node } from '@nextcloud/files'
import type { MountEntry } from '../services/externalStorage'

export const isNodeExternalStorage = function(node: Node) {
	// Not a folder, not a storage
	if (node.type === FileType.File) {
		 return false
	}

	// No backend or scope, not a storage
	const attributes = node.attributes as MountEntry
	if (!attributes.scope || !attributes.backend) {
		return false
	}

	// Specific markers that we're sure are ext storage only
	return attributes.scope === 'personal' || attributes.scope === 'system'
}
