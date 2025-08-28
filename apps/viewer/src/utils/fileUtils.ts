/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { FileStat } from 'webdav'

import { davRemoteURL, davRootPath } from '@nextcloud/files'
import { getLanguage } from '@nextcloud/l10n'
import { encodePath } from '@nextcloud/paths'
import { getCurrentUser } from '@nextcloud/auth'
import camelcase from 'camelcase'

import { isNumber } from './numberUtil'

export interface FileInfo {
	/** ID of the file (not unique if shared, use source instead) */
	fileid?: number
	/** Filename (name with path) */
	filename: string
	/** Basename of the file */
	basename: string
	/** DAV source URL */
	source: string
	/** File size in bytes */
	size: number
	/** E-Tag */
	etag?: string
	/** MIME type */
	mime?: string
	/** Last modification date */
	lastmod?: string
	/** File is marked as favorite */
	isFavorite?: boolean
	/** File type */
	type: 'directory'|'file'
	/** Attributes for file shares */
	shareAttributes?: string|Array<{value:boolean|string|number|null|object|Array<unknown>, key: string, scope: string}>
	/** Share hidden state since Nextcloud 31 */
	hideDownload?: boolean

	// custom attributes not fetch from API

	/** Does the file has an existing preview */
	hasPreview?: boolean
	/** URL of the preview image */
	previewUrl?: string
	/** The id of the peer live photo */
	metadataFilesLivePhoto?: number
	/** The absolute dav path */
	davPath?: string
	/** filename without extension */
	name?: string
}

/**
 * Extract dir and name from file path
 *
 * @param path the full path
 * @return [dirPath, fileName]
 */
export function extractFilePaths(path: string): [string, string] {
	const pathSections = path.split('/')
	const fileName = pathSections[pathSections.length - 1]
	const dirPath = pathSections.slice(0, pathSections.length - 1).join('/')
	return [dirPath, fileName]
}

/**
 * Extract path from source
 *
 * @param source the full source URL
 * @return path
 */
export function extractFilePathFromSource(source: string): string {
	const uid = getCurrentUser()?.uid

	if (uid) {
		const path = source.split(`${uid}/`)[1]
		if (path) {
			return path
		}
	}
	throw new Error(`Invalid source URL: ${source}. Unable to extract file paths.`)
}

/**
 * Sorting comparison function
 *
 * @param fileInfo1 file 1 FileInfo
 * @param fileInfo2 file 2 FileInfo
 * @param key key to sort with
 * @param asc sort ascending (default true)
 */
export function sortCompare(fileInfo1: FileInfo, fileInfo2: FileInfo, key: string, asc = true): number {

	if (fileInfo1.isFavorite && !fileInfo2.isFavorite) {
		return -1
	} else if (!fileInfo1.isFavorite && fileInfo2.isFavorite) {
		return 1
	}

	// if this is a number, let's sort by integer
	if (isNumber(fileInfo1[key]) && isNumber(fileInfo2[key])) {
		const result = Number(fileInfo1[key]) - Number(fileInfo2[key])
		return asc ? result : -result
	}

	// else we sort by string, so let's sort directories first
	if (fileInfo1.type === 'directory' && fileInfo2.type !== 'directory') {
		return -1
	} else if (fileInfo1.type !== 'directory' && fileInfo2.type === 'directory') {
		return 1
	}
	// sort by date if key is lastmod
	if (key === 'lastmod') {
		const result = new Date(fileInfo1.lastmod ?? 0).getTime() - new Date(fileInfo2.lastmod ?? 0).getTime()
		return asc ? -result : result
	}
	// finally sort by name
	return asc
		? fileInfo1[key].toString()?.localeCompare(fileInfo2[key].toString(), getLanguage(), { numeric: true })
		: -fileInfo1[key].toString()?.localeCompare(fileInfo2[key].toString(), getLanguage(), { numeric: true })
}

/**
 * Generate a FileInfo object based on the full dav properties
 * It will flatten everything and put all keys to camelCase
 * @param obj The stat response to convert
 */
export function genFileInfo(obj: FileStat): FileInfo {
	const fileInfo = {}

	Object.keys(obj).forEach(key => {
		const data = obj[key]

		// flatten object if any
		if (!!data && typeof data === 'object' && !Array.isArray(data)) {
			Object.assign(fileInfo, genFileInfo(data))
		} else {
			// format key and add it to the fileInfo
			if (data === 'false') {
				fileInfo[camelcase(key)] = false
			} else if (data === 'true') {
				fileInfo[camelcase(key)] = true
			} else {
				// preserve string typed properties as string (FileStat interface in webdav)
				const stringTypedProperties = ['filename', 'basename', 'owner-id']
				if (stringTypedProperties.includes(key)) {
					fileInfo[camelcase(key)] = String(data)
					return
				}
				fileInfo[camelcase(key)] = isNumber(data)
					? Number(data)
					: data
			}
		}
	})

	return fileInfo as FileInfo
}

/**
 * Generate absolute dav remote path of the file
 *
 * @param fileInfo The fileInfo
 * @param fileInfo.filename the file full path
 * @param fileInfo.source the file source if any
 */
export function getDavPath({ filename, source = '' }: { filename: string, source?: string }): string|null {
	if (!filename || typeof filename !== 'string') {
		return null
	}

	// If we have a source but we're not a dav resource, return null
	if (source && !source.includes(davRootPath)) {
		return null
	}

	// Workaround for files with different root like /remote.php/dav
	if (!filename.startsWith(davRootPath)) {
		filename = `${davRootPath}${filename}`
	}
	return davRemoteURL + encodePath(filename)
}
