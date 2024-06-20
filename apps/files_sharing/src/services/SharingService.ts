/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/* eslint-disable camelcase, n/no-extraneous-import */
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
			const mime = (await import('mime')).default
			// This won't catch files without an extension, but this is the best we can do
			ocsEntry.mimetype = mime.getType(ocsEntry.name)
			ocsEntry.item_type = ocsEntry.mimetype ? 'file' : 'folder'

			// Need to set permissions to NONE for federated shares
			ocsEntry.item_permissions = Permission.NONE
			ocsEntry.permissions = Permission.NONE

			ocsEntry.uid_owner = ocsEntry.owner
			// TODO: have the real display name stored somewhere
			ocsEntry.displayname_owner = ocsEntry.owner
		}

		const isFolder = ocsEntry?.item_type === 'folder'
		const hasPreview = ocsEntry?.has_preview === true
		const Node = isFolder ? Folder : File

		// If this is an external share that is not yet accepted,
		// we don't have an id. We can fallback to the row id temporarily
		const fileid = ocsEntry.file_source || ocsEntry.id

		// Generate path and strip double slashes
		const path = ocsEntry?.path || ocsEntry.file_target || ocsEntry.name
		const source = generateRemoteUrl(`dav/${rootPath}/${path}`.replaceAll(/\/\//gm, '/'))

		// Prefer share time if more recent than item mtime
		let mtime = ocsEntry?.item_mtime ? new Date((ocsEntry.item_mtime) * 1000) : undefined
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
				favorite: ocsEntry?.tags?.includes(window.OC.TAG_FAVORITE) ? 1 : 0,
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
 */
const groupBy = function(nodes: (Folder | File)[], key: string) {
	return Object.values(nodes.reduce(function(acc, curr) {
		(acc[curr[key]] = acc[curr[key]] || []).push(curr)
		return acc
	}, {})) as (Folder | File)[][]
}

export const getContents = async (sharedWithYou = true, sharedWithOthers = true, pendingShares = false, deletedshares = false, filterTypes: number[] = []): Promise<ContentsWithRoot> => {
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
