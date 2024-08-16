/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { Node, View, registerFileAction, FileAction, Permission } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { ShareType } from '@nextcloud/sharing'

import AccountGroupSvg from '@mdi/svg/svg/account-group.svg?raw'
import AccountPlusSvg from '@mdi/svg/svg/account-plus.svg?raw'
import LinkSvg from '@mdi/svg/svg/link.svg?raw'
import CircleSvg from '../../../../core/img/apps/circles.svg?raw'

import { getCurrentUser } from '@nextcloud/auth'
import { action as sidebarAction } from '../../../files/src/actions/sidebarAction'
import { generateAvatarSvg } from '../utils/AccountIcon'

import './sharingStatusAction.scss'

const isExternal = (node: Node) => {
	return node.attributes.remote_id !== undefined
}

export const action = new FileAction({
	id: 'sharing-status',
	displayName(nodes: Node[]) {
		const node = nodes[0]
		const shareTypes = Object.values(node?.attributes?.['share-types'] || {}).flat() as number[]
		const ownerId = node?.attributes?.['owner-id']

		if (shareTypes.length > 0
			|| (ownerId !== getCurrentUser()?.uid || isExternal(node))) {
			return t('files_sharing', 'Shared')
		}

		return ''
	},

	title(nodes: Node[]) {
		const node = nodes[0]
		const ownerId = node?.attributes?.['owner-id']
		const ownerDisplayName = node?.attributes?.['owner-display-name']

		// Mixed share types
		if (Array.isArray(node.attributes?.['share-types']) && node.attributes?.['share-types'].length > 1) {
			return t('files_sharing', 'Shared multiple times with different people')
		}

		if (ownerId && (ownerId !== getCurrentUser()?.uid || isExternal(node))) {
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
		if (shareTypes.includes(ShareType.Link)
			|| shareTypes.includes(ShareType.Email)) {
			return LinkSvg
		}

		// Group shares
		if (shareTypes.includes(ShareType.Grup)
			|| shareTypes.includes(ShareType.RemoteGroup)) {
			return AccountGroupSvg
		}

		// Circle shares
		if (shareTypes.includes(ShareType.Team)) {
			return CircleSvg
		}

		const ownerId = node?.attributes?.['owner-id']
		if (ownerId && (ownerId !== getCurrentUser()?.uid || isExternal(node))) {
			return generateAvatarSvg(ownerId, isExternal(node))
		}

		return AccountPlusSvg
	},

	enabled(nodes: Node[]) {
		if (nodes.length !== 1) {
			return false
		}

		const node = nodes[0]
		const ownerId = node?.attributes?.['owner-id']
		const shareTypes = node.attributes?.['share-types']
		const isMixed = Array.isArray(shareTypes) && shareTypes.length > 0

		// If the node is shared multiple times with
		// different share types to the current user
		if (isMixed) {
			return true
		}

		// If the node is shared by someone else
		if (ownerId && (ownerId !== getCurrentUser()?.uid || isExternal(node))) {
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
