/**
 * @copyright Copyright (c) 2024 John Molakvoæ <skjnldsv@protonmail.com>
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
