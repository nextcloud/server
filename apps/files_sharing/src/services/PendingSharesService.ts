/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode, Node } from '@nextcloud/files'

import axios from '@nextcloud/axios'
import { getRootPath } from '@nextcloud/files/dav'
import { generateOcsUrl } from '@nextcloud/router'
import { ShareType } from '@nextcloud/sharing'
import { getContents } from './SharingService.ts'
import { client, fetchNode } from '~/apps/files/src/services/WebdavClient.ts'

/**
 * Get the OCS url parameters for a share node
 *
 * @param node The share node
 */
function getShareParams(node: INode) {
	return {
		shareBase: node.attributes.remote ? 'remote_shares' : 'shares',
		id: node.attributes['share-id'],
	}
}

/**
 * Get the shares that still wait for the user to accept or decline them.
 * Declined group shares stay in the pending list, but also show up as deleted shares.
 */
export async function getPendingShares(): Promise<INode[]> {
	const [pending, deleted] = await Promise.all([
		getContents(false, false, true, false),
		getContents(false, false, false, true),
	])
	// Deleted share ids have a provider prefix, e.g. "ocinternal:123"
	const declinedIds = new Set(deleted.contents.map((node) => String(node.attributes['share-id']).split(':').pop()))
	return pending.contents.filter((node) => node.attributes.remote || !declinedIds.has(String(node.attributes['share-id'])))
}

/**
 * Whether a pending share can be rejected.
 * Rejected remote group shares land back in the pending list, so do not offer it.
 *
 * @param node The pending share node
 */
export function canRejectShare(node: INode): boolean {
	return !(node.attributes.remote_id && node.attributes.share_type === ShareType.RemoteGroup)
}

/**
 * Accept a pending share and return the node it is mounted as
 *
 * @param node The pending share node
 */
export async function acceptShare(node: INode): Promise<Node> {
	const params = getShareParams(node)
	await axios.post(generateOcsUrl('apps/files_sharing/api/v1/{shareBase}/pending/{id}', params))

	// The mount point can change on accept, e.g. on name conflicts
	const { data } = await axios.get(generateOcsUrl('apps/files_sharing/api/v1/{shareBase}/{id}', params))
	const path = node.attributes.remote ? data.ocs.data.mountpoint : data.ocs.data[0].path
	try {
		return await fetchNode(path)
	} catch (error) {
		if ((error as { status?: number }).status !== 404) {
			throw error
		}
		// A new federated mount can be missing until the user filesystem is set up again,
		// which listing the root folder does
		await client.getDirectoryContents(getRootPath())
		return fetchNode(path)
	}
}

/**
 * Reject a pending share
 *
 * @param node The pending share node
 */
export async function rejectShare(node: INode): Promise<void> {
	const url = node.attributes.accepted === 0
		? 'apps/files_sharing/api/v1/{shareBase}/pending/{id}'
		: 'apps/files_sharing/api/v1/{shareBase}/{id}'
	await axios.delete(generateOcsUrl(url, getShareParams(node)))
}
