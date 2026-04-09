/*!
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed } from 'webdav'

import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { getClient } from '@nextcloud/files/dav'
import { getCanonicalLocale } from '@nextcloud/l10n'
import { encodePath, join } from '@nextcloud/paths'
import { generateRemoteUrl, generateUrl } from '@nextcloud/router'
import davRequest from '../utils/davRequest.ts'
import logger from '../utils/logger.ts'

export interface Version {
	fileId: string // The id of the file associated to the version.
	label: string // 'Current version' or ''
	author: string | null // UID for the author of the version
	authorName: string | null // Display name of the author
	filename: string // File name relative to the version DAV endpoint
	basename: string // A base name generated from the mtime
	mime: string // Empty for the current version, else the actual mime type of the version
	etag: string // Empty for the current version, else the actual mime type of the version
	size: number // File size in bytes
	type: string // 'file'
	mtime: number // Version creation date as a timestamp
	permissions: string // Only readable: 'R'
	previewUrl: string // Preview URL of the version
	url: string // Download URL of the version
	source: string // The WebDAV endpoint of the resource
	fileVersion: string | null // The version id, null for the current version
}

const client = getClient()

/**
 * Get file versions for a given node
 *
 * @param node - The node to fetch versions for
 */
export async function fetchVersions(node: INode): Promise<Version[]> {
	const path = `/versions/${getCurrentUser()?.uid}/versions/${node.fileid}`

	try {
		const response = await client.getDirectoryContents(path, {
			data: davRequest,
			details: true,
		}) as ResponseDataDetailed<FileStat[]>

		const versions = response.data
			// Filter out root
			.filter(({ mime }) => mime !== '')
			.map((version) => formatVersion(version as Required<FileStat>, node))

		const authorIds = new Set(versions.map((version) => String(version.author)))
		const authors = await axios.post(generateUrl('/displaynames'), { users: [...authorIds] })

		for (const version of versions) {
			const author = authors.data.users[version.author ?? '']
			if (author) {
				version.authorName = author
			}
		}

		return versions
	} catch (exception) {
		logger.error('Could not fetch version', { exception })
		throw exception
	}
}

/**
 * Restore the given version
 *
 * @param version - The version to restore
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
 *
 * @param version - The version data from WebDAV
 * @param node - The original node
 */
function formatVersion(version: Required<FileStat>, node: INode): Version {
	const mtime = Date.parse(version.lastmod)
	let previewUrl = ''

	if (mtime === node.mtime?.getTime()) { // Version is the current one
		previewUrl = generateUrl('/core/preview?fileId={fileId}&c={fileEtag}&x=250&y=250&forceIcon=0&a=0&forceIcon=1&mimeFallback=1', {
			fileId: node.fileid,
			fileEtag: node.attributes.etag,
		})
	} else {
		previewUrl = generateUrl('/apps/files_versions/preview?file={file}&version={fileVersion}&mimeFallback=1', {
			file: node.path,
			fileVersion: version.basename,
		})
	}

	return {
		fileId: node.fileid!.toString(),
		// If version-label is defined make sure it is a string (prevent issue if the label is a number an PHP returns a number then)
		label: version.props['version-label'] ? String(version.props['version-label']) : '',
		author: version.props['version-author'] ? String(version.props['version-author']) : null,
		authorName: null,
		filename: version.filename,
		basename: new Date(mtime).toLocaleString(
			[getCanonicalLocale(), getCanonicalLocale().split('-')[0]!],
			{
				timeStyle: 'long',
				dateStyle: 'medium',
			},
		),
		mime: version.mime,
		etag: `${version.props.getetag}`,
		size: version.size,
		type: version.type,
		mtime,
		permissions: 'R',
		previewUrl,
		url: join('/remote.php/dav', version.filename),
		source: generateRemoteUrl('dav') + encodePath(version.filename),
		fileVersion: version.basename,
	}
}

/**
 * Set version label
 *
 * @param version - The version to set the label for
 * @param newLabel - The new label
 */
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

/**
 * Delete version
 *
 * @param version - The version to delete
 */
export async function deleteVersion(version: Version) {
	await client.deleteFile(version.filename)
}
