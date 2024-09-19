/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface BasicFileInfo {
	fileid: number // The file id
	filename: string // The file name, ex: /a/b/c/file.txt
	basename: string // The base name, ex: file.txt
	name: string // The name, ex: file
	source?: string // The source of the file, ex: https://example.org/remote.php/dav/files/userId/fileName.jpg
	previewUrl?: string // Optional URL of the file preview
	hasPreview: boolean // Does the file has an existing preview ?
	davPath: string // The absolute dav path
	etag: string|null // The etag of the file
	metadataFilesLivePhoto?: number // The id of the peer live photo
}
