/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ContentsWithRoot, Node } from '@nextcloud/files'

import { getCurrentUser } from '@nextcloud/auth'
import { getContents as getFiles } from './Files.ts'

const currentUserId = getCurrentUser()?.uid

/**
 * Filters each file/folder on its shared status
 *
 * A personal file is considered a file that has all of the following properties:
 * 1. the current user owns
 * 2. the file is not shared with anyone
 * 3. the file is not a group folder
 *
 * @todo Move to `@nextcloud/files`
 * @param node The node to check
 */
export function isPersonalFile(node: Node): boolean {
	// the type of mounts that determine whether the file is shared
	const sharedMountTypes = ['group', 'shared']
	const mountType = node.attributes['mount-type']

	return currentUserId === node.owner && !sharedMountTypes.includes(mountType)
}

/**
 * Get personal files from a given path
 *
 * @param path - The path to get the personal files from
 * @param options - Options
 * @param options.signal - Abort signal to cancel the request
 * @return A promise that resolves to the personal files
 */
export function getContents(path: string = '/', options: { signal: AbortSignal }): Promise<ContentsWithRoot> {
	// get all the files from the current path as a cancellable promise
	// then filter the files that the user does not own, or has shared / is a group folder
	return getFiles(path, options)
		.then((content) => {
			content.contents = content.contents.filter(isPersonalFile)
			return content
		})
}
