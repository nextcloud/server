/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
 * @return string if successful otherwise null if aborted
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
