/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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

import type { Node } from '@nextcloud/files'
import { spawnDialog } from '@nextcloud/dialogs'
import NewNodeDialog from '../components/NewNodeDialog.vue'

interface ILabels {
	/**
	 * Dialog heading, defaults to "New folder name"
	 */
	name?: string
	/**
	 * Label for input box, defaults to "New folder"
	 */
	label?: string
}

/**
 * Ask user for file or folder name
 * @param defaultName Default name to use
 * @param folderContent Nodes with in the current folder to check for unique name
 * @param labels Labels to set on the dialog
 * @return string if successfull otherwise null if aborted
 */
export function newNodeName(defaultName: string, folderContent: Node[], labels: ILabels = {}) {
	const contentNames = folderContent.map((node: Node) => node.basename)

	return new Promise<string|null>((resolve) => {
		spawnDialog(NewNodeDialog, {
			...labels,
			defaultName,
			otherNames: contentNames,
		}, (folderName) => {
			resolve(folderName as string|null)
		})
	})
}
