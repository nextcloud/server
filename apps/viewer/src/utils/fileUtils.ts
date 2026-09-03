/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { FileStat } from 'webdav'

import { getCurrentUser } from '@nextcloud/auth'
import camelcase from 'camelcase'
import { isNumber } from './numberUtil.ts'

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
	type: 'directory' | 'file'
	/** Attributes for file shares */
	shareAttributes?: string | Array<{ value: boolean | string | number | null | object | Array<unknown>, key: string, scope: string }>
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
	if (!fileName) {
		throw new Error(`Invalid path: ${path}. Unable to extract file name.`)
	}

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
 * Generate a FileInfo object based on the full dav properties
 * It will flatten everything and put all keys to camelCase
 *
 * @param obj The stat response to convert
 */
export function genFileInfo(obj: FileStat): FileInfo {
	const fileInfo = {}

	Object.keys(obj).forEach((key) => {
		const data = obj[key]

		// Skip structured DAV sub-trees that are not scalar file metadata
		// (e.g. nc:system-tags). Flattening them would camelCase the parsed
		// XML-attribute keys (prefixed with "@" by the WebDAV parser) into
		// invalid attribute names like "@canAssign", which crash v-bind /
		// setAttribute on Firefox and Safari. See richdocuments#5490.
		if (key === 'system-tags') {
			return
		}

		const ccKey = camelcase(key)

		// Never expose XML-attribute artifacts: the WebDAV parser prefixes
		// element attributes with "@", which camelcase preserves, yielding
		// invalid DOM qualified names that throw in setAttribute.
		if (ccKey.startsWith('@')) {
			return
		}

		// flatten object if any
		if (!!data && typeof data === 'object' && !Array.isArray(data)) {
			Object.assign(fileInfo, genFileInfo(data))
		} else {
			// format key and add it to the fileInfo
			if (data === 'false') {
				fileInfo[ccKey] = false
			} else if (data === 'true') {
				fileInfo[ccKey] = true
			} else {
				// preserve string typed properties as string (FileStat interface in webdav)
				const stringTypedProperties = ['filename', 'basename', 'owner-id']
				if (stringTypedProperties.includes(key)) {
					fileInfo[ccKey] = String(data)
					return
				}
				fileInfo[ccKey] = isNumber(data)
					? Number(data)
					: data
			}
		}
	})

	return fileInfo as FileInfo
}
