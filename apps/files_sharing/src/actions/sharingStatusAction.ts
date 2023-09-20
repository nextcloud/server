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
import { generateUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'

import './sharingStatusAction.scss'

const generateAvatarSvg = (userId: string) => {
	const avatarUrl = generateUrl('/avatar/{userId}/32', { userId })
	return `<svg width="32" height="32" viewBox="0 0 32 32"
		xmlns="http://www.w3.org/2000/svg" class="sharing-status__avatar">
		<image href="${avatarUrl}" height="32" width="32" />
	</svg>`
}

export const action = new FileAction({
	id: 'sharing-status',
	displayName(nodes: Node[]) {
		const node = nodes[0]
		const shareTypes = Object.values(node?.attributes?.['share-types'] || {}).flat() as number[]
		if (shareTypes.length > 0) {
			return t('files_sharing', 'Shared')
		}

		const ownerId = node?.attributes?.['owner-id']
		if (ownerId && ownerId !== getCurrentUser()?.uid) {
			return t('files_sharing', 'Shared')
		}

		return ''
	},
	iconSvgInline(nodes: Node[]) {
		const node = nodes[0]
		const shareTypes = Object.values(node?.attributes?.['share-types'] || {}).flat() as number[]

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

		const ownerId = node?.attributes?.['owner-id']
		if (ownerId && ownerId !== getCurrentUser()?.uid) {
			return generateAvatarSvg(ownerId)
		}

		return AccountPlusSvg
	},

	enabled(nodes: Node[]) {
		if (nodes.length !== 1) {
			return false
		}

		const node = nodes[0]
		const ownerId = node?.attributes?.['owner-id']
		if (ownerId && ownerId !== getCurrentUser()?.uid) {
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
