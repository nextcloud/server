/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
import type { AxiosPromise } from '@nextcloud/axios'
import type { OCSResponse } from '@nextcloud/typings/ocs'

import { Folder, File, type ContentsWithRoot, Permission } from '@nextcloud/files'
import { generateOcsUrl, generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'

import logger from './logger'

export const rootPath = `/files/${getCurrentUser()?.uid}`

const headers = {
	'Content-Type': 'application/json',
}

const ocsEntryToNode = async function(ocsEntry: any): Promise<Folder | File | null> {
	try {
		// Federated share handling
		if (ocsEntry?.remote_id !== undefined) {
			if (!ocsEntry.mimetype) {
				const mime = (await import('mime')).default
				// This won't catch files without an extension, but this is the best we can do
				ocsEntry.mimetype = mime.getType(ocsEntry.name)
			}
			ocsEntry.item_type = ocsEntry.type || (ocsEntry.mimetype ? 'file' : 'folder')

			// different naming for remote shares
			ocsEntry.item_mtime = ocsEntry.mtime
			ocsEntry.file_target = ocsEntry.file_target || ocsEntry.mountpoint

			// If the share is not accepted yet we don't know which permissions it will have
			if (!ocsEntry.accepted) {
				// Need to set permissions to NONE for federated shares
				ocsEntry.item_permissions = Permission.NONE
				ocsEntry.permissions = Permission.NONE
			}

			ocsEntry.uid_owner = ocsEntry.owner
			// TODO: have the real display name stored somewhere
			ocsEntry.displayname_owner = ocsEntry.owner
		}

		const isFolder = ocsEntry?.item_type === 'folder'
		const hasPreview = ocsEntry?.has_preview === true
		const Node = isFolder ? Folder : File

		// If this is an external share that is not yet accepted,
		// we don't have an id. We can fallback to the row id temporarily
		// local shares (this server) use `file_source`, but remote shares (federated) use `file_id`
		const fileid = ocsEntry.file_source || ocsEntry.file_id || ocsEntry.id

		// Generate path and strip double slashes
		const path = ocsEntry.path || ocsEntry.file_target || ocsEntry.name
		const source = generateRemoteUrl(`dav/${rootPath}/${path}`.replaceAll(/\/\//gm, '/'))

		let mtime = ocsEntry.item_mtime ? new Date((ocsEntry.item_mtime) * 1000) : undefined
		// Prefer share time if more recent than item mtime
		if (ocsEntry?.stime > (ocsEntry?.item_mtime || 0)) {
			mtime = new Date((ocsEntry.stime) * 1000)
		}

		return new Node({
			id: fileid,
			source,
			owner: ocsEntry?.uid_owner,
			mime: ocsEntry?.mimetype || 'application/octet-stream',
			mtime,
			size: ocsEntry?.item_size,
			permissions: ocsEntry?.item_permissions || ocsEntry?.permissions,
			root: rootPath,
			attributes: {
				...ocsEntry,
				'has-preview': hasPreview,
				// Also check the sharingStatusAction.ts code
				'owner-id': ocsEntry?.uid_owner,
				'owner-display-name': ocsEntry?.displayname_owner,
				'share-types': ocsEntry?.share_type,
				'share-attributes': ocsEntry?.attributes || '[]',
				favorite: ocsEntry?.tags?.includes((window.OC as Nextcloud.v29.OC & { TAG_FAVORITE: string }).TAG_FAVORITE) ? 1 : 0,
			},
		})
	} catch (error) {
		logger.error('Error while parsing OCS entry', { error })
		return null
	}
}

const getShares = function(shared_with_me = false): AxiosPromise<OCSResponse<any>> {
	const url = generateOcsUrl('apps/files_sharing/api/v1/shares')
	return axios.get(url, {
		headers,
		params: {
			shared_with_me,
			include_tags: true,
		},
	})
}

const getSharedWithYou = function(): AxiosPromise<OCSResponse<any>> {
	return getShares(true)
}

const getSharedWithOthers = function(): AxiosPromise<OCSResponse<any>> {
	return getShares()
}

const getRemoteShares = function(): AxiosPromise<OCSResponse<any>> {
	const url = generateOcsUrl('apps/files_sharing/api/v1/remote_shares')
	return axios.get(url, {
		headers,
		params: {
			include_tags: true,
		},
	})
}

const getPendingShares = function(): AxiosPromise<OCSResponse<any>> {
	const url = generateOcsUrl('apps/files_sharing/api/v1/shares/pending')
	return axios.get(url, {
		headers,
		params: {
			include_tags: true,
		},
	})
}

const getRemotePendingShares = function(): AxiosPromise<OCSResponse<any>> {
	const url = generateOcsUrl('apps/files_sharing/api/v1/remote_shares/pending')
	return axios.get(url, {
		headers,
		params: {
			include_tags: true,
		},
	})
}

const getDeletedShares = function(): AxiosPromise<OCSResponse<any>> {
	const url = generateOcsUrl('apps/files_sharing/api/v1/deletedshares')
	return axios.get(url, {
		headers,
		params: {
			include_tags: true,
		},
	})
}

/**
 * Group an array of objects (here Nodes) by a key
 * and return an array of arrays of them.
 * @param nodes Nodes to group
 * @param key The attribute to group by
 */
const groupBy = function(nodes: (Folder | File)[], key: string) {
	return Object.values(nodes.reduce(function(acc, curr) {
		(acc[curr[key]] = acc[curr[key]] || []).push(curr)
		return acc
	}, {})) as (Folder | File)[][]
}

export const getContents = async (sharedWithYou = true, sharedWithOthers = true, pendingShares = false, deletedshares = false, filterTypes: number[] = []): Promise<ContentsWithRoot> => {
	const promises = [] as AxiosPromise<OCSResponse<unknown>>[]

	if (sharedWithYou) {
		promises.push(getSharedWithYou(), getRemoteShares())
	}
	if (sharedWithOthers) {
		promises.push(getSharedWithOthers())
	}
	if (pendingShares) {
		promises.push(getPendingShares(), getRemotePendingShares())
	}
	if (deletedshares) {
		promises.push(getDeletedShares())
	}

	const responses = await Promise.all(promises)
	const data = responses.map((response) => response.data.ocs.data).flat()
	let contents = (await Promise.all(data.map(ocsEntryToNode)))
		.filter((node) => node !== null) as (Folder | File)[]

	if (filterTypes.length > 0) {
		contents = contents.filter((node) => filterTypes.includes(node.attributes?.share_type))
	}

	// Merge duplicate shares and group their attributes
	// Also check the sharingStatusAction.ts code
	contents = groupBy(contents, 'source').map((nodes) => {
		const node = nodes[0]
		node.attributes['share-types'] = nodes.map(node => node.attributes['share-types'])
		return node
	})

	return {
		folder: new Folder({
			id: 0,
			source: generateRemoteUrl('dav' + rootPath),
			owner: getCurrentUser()?.uid || null,
		}),
		contents,
	}
}
