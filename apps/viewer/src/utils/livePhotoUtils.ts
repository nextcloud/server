/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFile } from '@nextcloud/files'

const livePictureExt = ['jpg', 'jpeg', 'png']
const livePictureExtRegex = new RegExp(`\\.(${livePictureExt.join('|')})$`, 'i')

/**
 * Return the peer live photo from a list of files based on its fileId
 *
 * @param peerFileId - The fileId of the peer live photo to look for
 * @param fileList - The list of files to search within
 */
export function findLivePhotoPeerFromFileId(peerFileId: number, fileList: IFile[]): IFile | undefined {
	return fileList.find((file) => file.fileid === peerFileId)
}

/**
 * Return the peer live photo from a list of files based on the original file name.
 *
 * @param referenceFile - The file whose peer live photo should be found by name
 * @param fileList - The list of files to search within
 */
export function findLivePhotoPeerFromName(referenceFile: IFile, fileList: IFile[]): IFile | undefined {
	const extension = referenceFile.extension || ''
	const nameWithoutExt = referenceFile.basename.slice(0, -(extension.length + 1))
	return fileList.find((comparedFile) => {
		// if same filename and extension is allowed
		return comparedFile.source !== referenceFile.source
			&& (comparedFile.basename.startsWith(nameWithoutExt) && livePictureExtRegex.test(comparedFile.basename))
	})
}
