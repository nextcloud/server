/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'

import { spawnDialog } from '@nextcloud/vue/functions/dialog'
import NewNodeDialog from '../components/NewNodeDialog.vue'

interface NewNodeDialogOptions {
	/**
	 * Dialog heading, defaults to "New folder name"
	 */
	name?: string
	/**
	 * Label for input box, defaults to "New folder"
	 */
	label?: string

	/**
	 * Whether the name is for a folder, defaults to false.
	 */
	isFolder?: boolean
}

/**
 * Ask user for file or folder name
 *
 * @param defaultName Default name to use
 * @param folderContent Nodes or names within the current folder to check for unique name
 * @param options Options for the dialog
 * @return string if successful otherwise null if aborted
 */
export function newNodeName(defaultName: string, folderContent: (INode | string)[], options: NewNodeDialogOptions = {}) {
	const contentNames = folderContent.map((node) => typeof node === 'string' ? node : node.basename)

	return new Promise<string | null>((resolve) => {
		spawnDialog(NewNodeDialog, {
			...options,
			defaultName,
			otherNames: contentNames,
		}, (folderName) => {
			resolve(folderName as string | null)
		})
	})
}
