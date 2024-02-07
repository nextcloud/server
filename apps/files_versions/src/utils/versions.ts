/* eslint-disable @typescript-eslint/no-explicit-any */
/* eslint-disable jsdoc/require-param */
/* eslint-disable jsdoc/require-jsdoc */
/**
 * @copyright 2022 Louis Chemineau <mlouis@chmn.me>
 *
 * @author Louis Chemineau <mlouis@chmn.me>
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
 */

import { getCurrentUser } from '@nextcloud/auth'
import { joinPaths } from '@nextcloud/paths'
import { generateRemoteUrl, generateUrl } from '@nextcloud/router'
import moment from '@nextcloud/moment'

import { encodeFilePath } from '../../../files/src/utils/fileUtils.ts'

import client from '../utils/davClient.js'
import davRequest from '../utils/davRequest.js'
import logger from '../utils/logger.js'
import type { FileStat, ResponseDataDetailed } from 'webdav'

export interface Version {
	fileId: string, // The id of the file associated to the version.
	label: string, // 'Current version' or ''
	filename: string, // File name relative to the version DAV endpoint
	basename: string, // A base name generated from the mtime
	mime: string, // Empty for the current version, else the actual mime type of the version
	etag: string, // Empty for the current version, else the actual mime type of the version
	size: string, // Human readable size
	type: string, // 'file'
	mtime: number, // Version creation date as a timestamp
	permissions: string, // Only readable: 'R'
	hasPreview: boolean, // Whether the version has a preview
	previewUrl: string, // Preview URL of the version
	url: string, // Download URL of the version
	source: string, // The WebDAV endpoint of the ressource
	fileVersion: string|null, // The version id, null for the current version
}

export async function fetchVersions(fileInfo: any): Promise<Version[]> {
	const path = `/versions/${getCurrentUser()?.uid}/versions/${fileInfo.id}`

	try {
		const response = await client.getDirectoryContents(path, {
			data: davRequest,
			details: true,
		}) as ResponseDataDetailed<FileStat[]>

		return response.data
			// Filter out root
			.filter(({ mime }) => mime !== '')
			.map(version => formatVersion(version, fileInfo))
	} catch (exception) {
		logger.error('Could not fetch version', { exception })
		throw exception
	}
}

/**
 * Restore the given version
 */
export async function restoreVersion(version: Version) {
	try {
		logger.debug('Restoring version', { url: version.url })
		await client.moveFile(
			`/versions/${getCurrentUser()?.uid}/versions/${version.fileId}/${version.fileVersion}`,
			`/versions/${getCurrentUser()?.uid}/restore/target`,
		)
	} catch (exception) {
		logger.error('Could not restore version', { exception })
		throw exception
	}
}

/**
 * Format version
 */
function formatVersion(version: any, fileInfo: any): Version {
	const mtime = moment(version.lastmod).unix() * 1000
	let previewUrl = ''

	if (mtime === fileInfo.mtime) { // Version is the current one
		previewUrl = generateUrl('/core/preview?fileId={fileId}&c={fileEtag}&x=250&y=250&forceIcon=0&a=0', {
			fileId: fileInfo.id,
			fileEtag: fileInfo.etag,
		})
	} else {
		previewUrl = generateUrl('/apps/files_versions/preview?file={file}&version={fileVersion}', {
			file: joinPaths(fileInfo.path, fileInfo.name),
			fileVersion: version.basename,
		})
	}

	return {
		fileId: fileInfo.id,
		label: version.props['version-label'],
		filename: version.filename,
		basename: moment(mtime).format('LLL'),
		mime: version.mime,
		etag: `${version.props.getetag}`,
		size: version.size,
		type: version.type,
		mtime,
		permissions: 'R',
		hasPreview: version.props['has-preview'] === 1,
		previewUrl,
		url: joinPaths('/remote.php/dav', version.filename),
		source: generateRemoteUrl('dav') + encodeFilePath(version.filename),
		fileVersion: version.basename,
	}
}

export async function setVersionLabel(version: Version, newLabel: string) {
	return await client.customRequest(
		version.filename,
		{
			method: 'PROPPATCH',
			data: `<?xml version="1.0"?>
					<d:propertyupdate xmlns:d="DAV:"
						xmlns:oc="http://owncloud.org/ns"
						xmlns:nc="http://nextcloud.org/ns"
						xmlns:ocs="http://open-collaboration-services.org/ns">
					<d:set>
						<d:prop>
							<nc:version-label>${newLabel}</nc:version-label>
						</d:prop>
					</d:set>
					</d:propertyupdate>`,
		},
	)
}

export async function deleteVersion(version: Version) {
	await client.deleteFile(version.filename)
}
