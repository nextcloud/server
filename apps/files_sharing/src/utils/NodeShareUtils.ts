/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'
import type { Node } from '@nextcloud/files'
import { Type } from '@nextcloud/sharing'

type Share = {
	/** The recipient display name */
	'display-name': string
	/** The recipient user id */
	id: string
	/** The share type */
	type: Type
}

const getSharesAttribute = function(node: Node) {
	return Object.values(node.attributes.sharees).flat() as Share[]
}

export const isNodeSharedWithMe = function(node: Node) {
	const uid = getCurrentUser()?.uid
	const shares = getSharesAttribute(node)

	// If you're the owner, you can't share with yourself
	if (node.owner === uid) {
		return false
	}

	return shares.length > 0 && (
		// If some shares are shared with you as a direct user share
		shares.some(share => share.id === uid && share.type === Type.SHARE_TYPE_USER)
		// Or of the file is shared with a group you're in
		// (if it's returned by the backend, we assume you're in it)
		|| shares.some(share => share.type === Type.SHARE_TYPE_GROUP)
	)
}

export const isNodeSharedWithOthers = function(node: Node) {
	const uid = getCurrentUser()?.uid
	const shares = getSharesAttribute(node)

	// If you're NOT the owner, you can't share with yourself
	if (node.owner === uid) {
		return false
	}

	return shares.length > 0
		// If some shares are shared with you as a direct user share
		&& shares.some(share => share.id !== uid && share.type !== Type.SHARE_TYPE_GROUP)
}

export const isNodeShared = function(node: Node) {
	const shares = getSharesAttribute(node)
	return shares.length > 0
}
