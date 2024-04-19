/**
 * @copyright Copyright (c) 2024 Eduardo Morales <emoral435@gmail.com>
 *
 * @author Eduardo Morales <emoral435@gmail.com>
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