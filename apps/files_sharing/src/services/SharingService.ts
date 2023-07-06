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
/* eslint-disable camelcase, n/no-extraneous-import */
import type { AxiosPromise } from 'axios'
import type { ContentsWithRoot } from '../../../files/src/services/Navigation'

import { Folder, File } from '@nextcloud/files'
import { generateOcsUrl, generateRemoteUrl, generateUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import logger from './logger'

export const rootPath = `/files/${getCurrentUser()?.uid}`

export type OCSResponse = {
	ocs: {
		meta: {
			status: string
			statuscode: number
			message: string
		},
		data: []
	}
}

const headers = {
	'Content-Type': 'application/json',
}

const ocsEntryToNode = function(ocsEntry: any): Folder | File | null {
	try {
		const isFolder = ocsEntry?.item_type === 'folder'
		const hasPreview = ocsEntry?.has_preview === true
		const Node = isFolder ? Folder : File

		const fileid = ocsEntry.file_source
		const previewUrl = hasPreview ? generateUrl('/core/preview?fileId={fileid}&x=32&y=32&forceIcon=0', { fileid }) : undefined

		// Generate path and strip double slashes
		const path = ocsEntry?.path || ocsEntry.file_target
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
			mime: ocsEntry?.mimetype,
			mtime,
			size: ocsEntry?.item_size,
			permissions: ocsEntry?.item_permissions || ocsEntry?.permissions,
			root: rootPath,
			attributes: {
				...ocsEntry,
				previewUrl,
				'has-preview': hasPreview,
				favorite: ocsEntry?.tags?.includes(window.OC.TAG_FAVORITE) ? 1 : 0,
			},
		})
	} catch (error) {
		logger.error('Error while parsing OCS entry', error)
		return null
	}
}

const getShares = function(shared_with_me = false): AxiosPromise<OCSResponse> {
	const url = generateOcsUrl('apps/files_sharing/api/v1/shares')
	return axios.get(url, {
		headers,
		params: {
			shared_with_me,
			include_tags: true,
		},
	})
}

const getSharedWithYou = function(): AxiosPromise<OCSResponse> {
	return getShares(true)
}

const getSharedWithOthers = function(): AxiosPromise<OCSResponse> {
	return getShares()
}

const getRemoteShares = function(): AxiosPromise<OCSResponse> {
	const url = generateOcsUrl('apps/files_sharing/api/v1/remote_shares')
	return axios.get(url, {
		headers,
		params: {
			include_tags: true,
		},
	})
}

const getPendingShares = function(): AxiosPromise<OCSResponse> {
	const url = generateOcsUrl('apps/files_sharing/api/v1/shares/pending')
	return axios.get(url, {
		headers,
		params: {
			include_tags: true,
		},
	})
}

const getRemotePendingShares = function(): AxiosPromise<OCSResponse> {
	const url = generateOcsUrl('apps/files_sharing/api/v1/remote_shares/pending')
	return axios.get(url, {
		headers,
		params: {
			include_tags: true,
		},
	})
}

const getDeletedShares = function(): AxiosPromise<OCSResponse> {
	const url = generateOcsUrl('apps/files_sharing/api/v1/deletedshares')
	return axios.get(url, {
		headers,
		params: {
			include_tags: true,
		},
	})
}

export const getContents = async (sharedWithYou = true, sharedWithOthers = true, pendingShares = false, deletedshares = false, filterTypes: number[] = []): Promise<ContentsWithRoot> => {
	const promises = [] as AxiosPromise<OCSResponse>[]

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
	let contents = data.map(ocsEntryToNode).filter((node) => node !== null) as (Folder | File)[]

	if (filterTypes.length > 0) {
		contents = contents.filter((node) => filterTypes.includes(node.attributes?.share_type))
	}

	return {
		folder: new Folder({
			id: 0,
			source: generateRemoteUrl('dav' + rootPath),
			owner: getCurrentUser()?.uid || null,
		}),
		contents,
	}
}

