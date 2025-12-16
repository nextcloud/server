/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
// TODO: Fix this instead of disabling ESLint!!!
/* eslint-disable @typescript-eslint/no-explicit-any */

import type { AxiosPromise } from '@nextcloud/axios'
import type { ContentsWithRoot } from '@nextcloud/files'
import type { OCSResponse } from '@nextcloud/typings/ocs'
import type { ShareAttribute } from '../sharing.d.ts'

import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { File, Folder, Permission } from '@nextcloud/files'
import { getRemoteURL, getRootPath } from '@nextcloud/files/dav'
import { generateOcsUrl } from '@nextcloud/router'
import logger from './logger.ts'

const headers = {
	'Content-Type': 'application/json',
}

/**
 *
 * @param ocsEntry
 */
async function ocsEntryToNode(ocsEntry: any): Promise<Folder | File | null> {
	try {
		// Federated share handling
		if (ocsEntry?.remote_id !== undefined) {
			if (!ocsEntry.mimetype) {
				const mime = (await import('mime')).default
				// This won't catch files without an extension, but this is the best we can do
				ocsEntry.mimetype = mime.getType(ocsEntry.name)
			}
			const type = ocsEntry.type === 'dir' ? 'folder' : ocsEntry.type
			ocsEntry.item_type = type || (ocsEntry.mimetype ? 'file' : 'folder')

			// different naming for remote shares
			ocsEntry.item_mtime = ocsEntry.mtime
			ocsEntry.file_target = ocsEntry.file_target || ocsEntry.mountpoint

			if (ocsEntry.file_target.includes('TemporaryMountPointName')) {
				ocsEntry.file_target = ocsEntry.name
			}

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
		const source = `${getRemoteURL()}${getRootPath()}/${path.replace(/^\/+/, '')}`

		let mtime = ocsEntry.item_mtime ? new Date((ocsEntry.item_mtime) * 1000) : undefined
		// Prefer share time if more recent than item mtime
		if (ocsEntry?.stime > (ocsEntry?.item_mtime || 0)) {
			mtime = new Date((ocsEntry.stime) * 1000)
		}

		let sharees: { sharee: object } | undefined
		if ('share_with' in ocsEntry) {
			sharees = {
				sharee: {
					id: ocsEntry.share_with,
					'display-name': ocsEntry.share_with_displayname || ocsEntry.share_with,
					type: ocsEntry.share_type,
				},
			}
		}

		return new Node({
			id: fileid,
			source,
			owner: ocsEntry?.uid_owner,
			mime: ocsEntry?.mimetype || 'application/octet-stream',
			mtime,
			size: ocsEntry?.item_size,
			permissions: ocsEntry?.item_permissions || ocsEntry?.permissions,
			root: getRootPath(),
			attributes: {
				...ocsEntry,
				'has-preview': hasPreview,
				'hide-download': ocsEntry?.hide_download === 1,
				// Also check the sharingStatusAction.ts code
				'owner-id': ocsEntry?.uid_owner,
				'owner-display-name': ocsEntry?.displayname_owner,
				'share-types': ocsEntry?.share_type,
				'share-attributes': ocsEntry?.attributes || '[]',
				sharees,
				favorite: ocsEntry?.tags?.includes((window.OC as { TAG_FAVORITE: string }).TAG_FAVORITE) ? 1 : 0,
			},
		})
	} catch (error) {
		logger.error('Error while parsing OCS entry', { error })
		return null
	}
}

/**
 *
 * @param shareWithMe
 */
function getShares(shareWithMe = false): AxiosPromise<OCSResponse<any>> {
	const url = generateOcsUrl('apps/files_sharing/api/v1/shares')
	return axios.get(url, {
		headers,
		params: {
			shared_with_me: shareWithMe,
			include_tags: true,
		},
	})
}

/**
 *
 */
function getSharedWithYou(): AxiosPromise<OCSResponse<any>> {
	return getShares(true)
}

/**
 *
 */
function getSharedWithOthers(): AxiosPromise<OCSResponse<any>> {
	return getShares()
}

/**
 *
 */
function getRemoteShares(): AxiosPromise<OCSResponse<any>> {
	const url = generateOcsUrl('apps/files_sharing/api/v1/remote_shares')
	return axios.get(url, {
		headers,
		params: {
			include_tags: true,
		},
	})
}

/**
 *
 */
function getPendingShares(): AxiosPromise<OCSResponse<any>> {
	const url = generateOcsUrl('apps/files_sharing/api/v1/shares/pending')
	return axios.get(url, {
		headers,
		params: {
			include_tags: true,
		},
	})
}

/**
 *
 */
function getRemotePendingShares(): AxiosPromise<OCSResponse<any>> {
	const url = generateOcsUrl('apps/files_sharing/api/v1/remote_shares/pending')
	return axios.get(url, {
		headers,
		params: {
			include_tags: true,
		},
	})
}

/**
 *
 */
function getDeletedShares(): AxiosPromise<OCSResponse<any>> {
	const url = generateOcsUrl('apps/files_sharing/api/v1/deletedshares')
	return axios.get(url, {
		headers,
		params: {
			include_tags: true,
		},
	})
}

/**
 * Check if a file request is enabled
 *
 * @param attributes the share attributes json-encoded array
 */
export function isFileRequest(attributes = '[]'): boolean {
	const isFileRequest = (attribute) => {
		return attribute.scope === 'fileRequest' && attribute.key === 'enabled' && attribute.value === true
	}

	try {
		const attributesArray = JSON.parse(attributes) as Array<ShareAttribute>
		return attributesArray.some(isFileRequest)
	} catch (error) {
		logger.error('Error while parsing share attributes', { error })
		return false
	}
}

/**
 * Group an array of objects (here Nodes) by a key
 * and return an array of arrays of them.
 *
 * @param nodes Nodes to group
 * @param key The attribute to group by
 */
function groupBy(nodes: (Folder | File)[], key: string) {
	return Object.values(nodes.reduce(function(acc, curr) {
		(acc[curr[key]] = acc[curr[key]] || []).push(curr)
		return acc
	}, {})) as (Folder | File)[][]
}

/**
 *
 * @param sharedWithYou
 * @param sharedWithOthers
 * @param pendingShares
 * @param deletedshares
 * @param filterTypes
 */
export async function getContents(sharedWithYou = true, sharedWithOthers = true, pendingShares = false, deletedshares = false, filterTypes: number[] = []): Promise<ContentsWithRoot> {
	const promises = [] as AxiosPromise<OCSResponse<any>>[]

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
		node.attributes['share-types'] = nodes.map((node) => node.attributes['share-types'])
		return node
	})

	return {
		folder: new Folder({
			id: 0,
			source: `${getRemoteURL()}${getRootPath()}`,
			owner: getCurrentUser()?.uid || null,
			root: getRootPath(),
		}),
		contents,
	}
}
