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

import { encodeFilePath } from '../../../files/src/utils/fileUtils.js'

import client from '../utils/davClient.js'
import davRequest from '../utils/davRequest.js'
import logger from '../utils/logger.js'

/**
 * @typedef {object} Version
 * @property {string} fileId - The id of the file associated to the version.
 * @property {string} label - 'Current version' or ''
 * @property {string} filename - File name relative to the version DAV endpoint
 * @property {string} basename - A base name generated from the mtime
 * @property {string} mime - Empty for the current version, else the actual mime type of the version
 * @property {string} etag - Empty for the current version, else the actual mime type of the version
 * @property {string} size - Human readable size
 * @property {string} type - 'file'
 * @property {number} mtime - Version creation date as a timestamp
 * @property {string} permissions - Only readable: 'R'
 * @property {boolean} hasPreview - Whether the version has a preview
 * @property {string} previewUrl - Preview URL of the version
 * @property {string} url - Download URL of the version
 * @property {string} source - The WebDAV endpoint of the ressource
 * @property {string|null} fileVersion - The version id, null for the current version
 */

/**
 * @param fileInfo
 * @return {Promise<Version[]>}
 */
export async function fetchVersions(fileInfo) {
	const path = `/versions/${getCurrentUser()?.uid}/versions/${fileInfo.id}`

	try {
		/** @type {import('webdav').ResponseDataDetailed<import('webdav').FileStat[]>} */
		const response = await client.getDirectoryContents(path, {
			data: davRequest,
			details: true,
		})
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
 *
 * @param {Version} version
 */
export async function restoreVersion(version) {
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
 *
 * @param {object} version - raw version received from the versions DAV endpoint
 * @param {object} fileInfo - file properties received from the files DAV endpoint
 * @return {Version}
 */
function formatVersion(version, fileInfo) {
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

/**
 * @param {Version} version
 * @param {string} newLabel
 */
export async function setVersionLabel(version, newLabel) {
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

/**
 * @param {Version} version
 */
export async function deleteVersion(version) {
	await client.deleteFile(version.filename)
}
