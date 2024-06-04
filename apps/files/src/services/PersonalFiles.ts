/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { File, type ContentsWithRoot } from '@nextcloud/files'
import { getCurrentUser } from '@nextcloud/auth';

import { getContents as getFiles } from './Files';

const currUserID = getCurrentUser()?.uid

/**
 * NOTE MOVE TO @nextcloud/files 
 * @brief filters each file/folder on its shared status
 * 	A personal file is considered a file that has all of the following properties:
 * 		a.) the current user owns
 * 		b.) the file is not shared with anyone
 * 		c.) the file is not a group folder
 * @param {FileStat} node that contains  
 * @return {Boolean}
 */
export const isPersonalFile = function(node: File): Boolean {
	// the type of mounts that determine whether the file is shared
	const sharedMountTypes = ["group", "shared"]
	const mountType = node.attributes['mount-type']
	// the check to determine whether the current logged in user is the owner / creator of the node
	const currUserCreated = currUserID ? node.owner === currUserID : true

	return currUserCreated && !sharedMountTypes.includes(mountType)
}

export const getContents = (path: string = "/"): Promise<ContentsWithRoot> => {
	// get all the files from the current path as a cancellable promise
	// then filter the files that the user does not own, or has shared / is a group folder
    return getFiles(path)
		.then(c => {
			c.contents = c.contents.filter(isPersonalFile) as File[]
			return c
		})
}