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
import client from '../utils/davClient.js'
import davRequest from '../utils/davRequest.js'
import logger from '../utils/logger.js'
import { joinPaths } from '@nextcloud/paths'
import { generateUrl } from '@nextcloud/router'
import moment from '@nextcloud/moment'

/**
 * @typedef {object} Version
 * @property {string} fileId - The id of the file associated to the version.
 * @property {string} title - 'Current version' or ''
 * @property {string} fileName - File name relative to the version DAV endpoint
 * @property {string} mimeType - Empty for the current version, else the actual mime type of the version
 * @property {string} size - Human readable size
 * @property {string} type - 'file'
 * @property {number} mtime - Version creation date as a timestamp
 * @property {string} preview - Preview URL of the version
 * @property {string} url - Download URL of the version
 * @property {string|null} fileVersion - The version id, null for the current version
 */

/**
 * @param fileInfo
 * @return {Promise<Version[]>}
 */
export async function fetchVersions(fileInfo) {
	const path = `/versions/${getCurrentUser()?.uid}/versions/${fileInfo.id}`

	try {
		/** @type {import('webdav').FileStat[]} */
		const response = await client.getDirectoryContents(path, {
			data: davRequest,
		})
		return response
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
			`/versions/${getCurrentUser()?.uid}/restore/target`
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
	return {
		fileId: fileInfo.id,
		title: '',
		fileName: version.filename,
		mimeType: version.mime,
		size: version.size,
		type: version.type,
		mtime: moment(version.lastmod).unix() * 1000,
		preview: generateUrl('/apps/files_versions/preview?file={file}&version={fileVersion}', {
			file: joinPaths(fileInfo.path, fileInfo.name),
			fileVersion: version.basename,
		}),
		url: joinPaths('/remote.php/dav', version.filename),
		fileVersion: version.basename,
	}
}

/**
 * @param {Version} version
 * @param {string} newName
 */
export async function setVersionName(version, newName) {
	// await fetch('POST', '/setVersionName')
}

/**
 * @param {Version} version
 */
export async function deleteVersion(version) {
	// await fetch('DELETE', '/version')
}
