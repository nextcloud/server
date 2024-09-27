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
import { Node, View, registerFileAction, FileAction, Permission } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { Type } from '@nextcloud/sharing'

import AccountGroupSvg from '@mdi/svg/svg/account-group.svg?raw'
import AccountPlusSvg from '@mdi/svg/svg/account-plus.svg?raw'
import LinkSvg from '@mdi/svg/svg/link.svg?raw'
import CircleSvg from '../../../../core/img/apps/circles.svg?raw'

import { action as sidebarAction } from '../../../files/src/actions/sidebarAction'
import { getCurrentUser } from '@nextcloud/auth'
import { generateAvatarSvg } from '../utils/AccountIcon.ts'

import './sharingStatusAction.scss'

const isExternal = (node: Node) => {
	return node.attributes.remote_id !== undefined
}

export const action = new FileAction({
	id: 'sharing-status',
	displayName(nodes: Node[]) {
		const node = nodes[0]
		const shareTypes = Object.values(node?.attributes?.['share-types'] || {}).flat() as number[]

		if (shareTypes.length > 0
			|| (node.owner !== getCurrentUser()?.uid || isExternal(node))) {
			return t('files_sharing', 'Shared')
		}

		return ''
	},

	title(nodes: Node[]) {
		const node = nodes[0]

		// Mixed share types
		if (Array.isArray(node.attributes?.['share-types']) && node.attributes?.['share-types'].length > 1) {
			return t('files_sharing', 'Shared multiple times with different people')
		}

		if (node.owner && (node.owner !== getCurrentUser()?.uid || isExternal(node))) {
			const ownerDisplayName = node?.attributes?.['owner-display-name']
			return t('files_sharing', 'Shared by {ownerDisplayName}', { ownerDisplayName })
		}

		return t('files_sharing', 'Show sharing options')
	},

	iconSvgInline(nodes: Node[]) {
		const node = nodes[0]
		const shareTypes = Object.values(node?.attributes?.['share-types'] || {}).flat() as number[]

		// Mixed share types
		if (Array.isArray(node.attributes?.['share-types']) && node.attributes?.['share-types'].length > 1) {
			return AccountPlusSvg
		}

		// Link shares
		if (shareTypes.includes(Type.SHARE_TYPE_LINK)
			|| shareTypes.includes(Type.SHARE_TYPE_EMAIL)) {
			return LinkSvg
		}

		// Group shares
		if (shareTypes.includes(Type.SHARE_TYPE_GROUP)
			|| shareTypes.includes(Type.SHARE_TYPE_REMOTE_GROUP)) {
			return AccountGroupSvg
		}

		// Circle shares
		if (shareTypes.includes(Type.SHARE_TYPE_CIRCLE)) {
			return CircleSvg
		}

		if (node.owner && (node.owner !== getCurrentUser()?.uid || isExternal(node))) {
			const sanitizeId = (id: string) => id.replace(/[^a-zA-Z0-9._%+@-]+/g, '').replace(/\//g, '')
			return generateAvatarSvg(sanitizeId(node.owner), isExternal(node))
		}

		return AccountPlusSvg
	},

	enabled(nodes: Node[]) {
		if (nodes.length !== 1) {
			return false
		}

		const node = nodes[0]
		const shareTypes = node.attributes?.['share-types']
		const isMixed = Array.isArray(shareTypes) && shareTypes.length > 0

		// If the node is shared multiple times with
		// different share types to the current user
		if (isMixed) {
			return true
		}

		// If the node is shared by someone else
		if (node.owner && (node.owner !== getCurrentUser()?.uid || isExternal(node))) {
			return true
		}

		return (node.permissions & Permission.SHARE) !== 0
	},

	async exec(node: Node, view: View, dir: string) {
		// You need read permissions to see the sidebar
		if ((node.permissions & Permission.READ) !== 0) {
			window.OCA?.Files?.Sidebar?.setActiveTab?.('sharing')
			return sidebarAction.exec(node, view, dir)
		}
		return null
	},

	inline: () => true,

})

registerFileAction(action)
