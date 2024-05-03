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

import { ref } from 'vue'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { getContents } from '../services/trashbin.ts'

export const useTrashbinStore = () => {
	const nodes = ref<Node[]>([])

	const remove = (file: Node) => {
		nodes.value = nodes.value.filter(node => node.fileid !== file.fileid)
	}

	const init = async () => {
		nodes.value = (await getContents()).contents
		subscribe('files:node:deleted', remove)
	}

	const reset = () => {
		nodes.value = []
		unsubscribe('files:node:deleted', remove)
	}

	return {
		nodes,
		init,
		reset,
	}
}
