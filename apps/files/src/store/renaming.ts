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
/* eslint-disable */
import { defineStore } from 'pinia'
import { subscribe } from '@nextcloud/event-bus'
import type { Node } from '@nextcloud/files'
import type { RenamingStore } from '../types'

export const useRenamingStore = function() {
	const store = defineStore('renaming', {
		state: () => ({
			renamingNode: undefined,
			newName: '',
		} as RenamingStore),
	})

	const renamingStore = store(...arguments)

	// Make sure we only register the listeners once
	if (!renamingStore._initialized) {
		subscribe('files:node:rename', function(node: Node) {
			renamingStore.renamingNode = node
			renamingStore.newName = node.basename
		})
		renamingStore._initialized = true
	}

	return renamingStore
}
