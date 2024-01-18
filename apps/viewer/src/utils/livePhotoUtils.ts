/**
 * @copyright Copyright (c) 2023 Louis Chmn <louis@chmn.me>
 *
 * @author Louis Chmn <louis@chmn.me>
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

import type { BasicFileInfo } from './models'

const livePictureExt = ['jpg', 'jpeg', 'png']
const livePictureExtRegex = new RegExp(`\\.(${livePictureExt.join('|')})$`, 'i')

/**
 * Return the peer live photo from a list of files based on its fileId
 * @param peerFileId
 * @param fileList
 */
export function findLivePhotoPeerFromFileId(peerFileId: number, fileList: BasicFileInfo[]): BasicFileInfo | undefined {
	return fileList.find(file => file.fileid === peerFileId)
}

/**
 * Return the peer live photo from a list of files based on the original file name.
 * @param referenceFile
 * @param fileList
 */
export function findLivePhotoPeerFromName(referenceFile: BasicFileInfo, fileList: BasicFileInfo[]): BasicFileInfo | undefined {
	return fileList.find(comparedFile => {
		// if same filename and extension is allowed
		return comparedFile.filename !== referenceFile.filename
				&& (comparedFile.basename.startsWith(referenceFile.name) && livePictureExtRegex.test(comparedFile.basename))
	})
}
