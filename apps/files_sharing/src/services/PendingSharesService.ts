/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode, Node } from '@nextcloud/files'

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { ShareType } from '@nextcloud/sharing'
import { fetchNode } from '~/apps/files/src/services/WebdavClient.ts'

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
	return fetchNode(path)
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
