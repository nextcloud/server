/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Node } from '@nextcloud/files'

import AccountGroupSvg from '@mdi/svg/svg/account-group-outline.svg?raw'
import AccountPlusSvg from '@mdi/svg/svg/account-plus-outline.svg?raw'
import LinkSvg from '@mdi/svg/svg/link.svg?raw'
import { getCurrentUser } from '@nextcloud/auth'
import { showError } from '@nextcloud/dialogs'
import { FileAction, Permission, registerFileAction } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { ShareType } from '@nextcloud/sharing'
import { isPublicShare } from '@nextcloud/sharing/public'
import CircleSvg from '../../../../core/img/apps/circles.svg?raw'
import { action as sidebarAction } from '../../../files/src/actions/sidebarAction.ts'
import { generateAvatarSvg } from '../utils/AccountIcon.ts'

import './sharingStatusAction.scss'

/**
 *
 * @param node
 */
function isExternal(node: Node) {
	return node.attributes?.['is-federated'] ?? false
}

export const ACTION_SHARING_STATUS = 'sharing-status'
export const action = new FileAction({
	id: ACTION_SHARING_STATUS,
	displayName({ nodes }) {
		const node = nodes[0]!
		const shareTypes = Object.values(node?.attributes?.['share-types'] || {}).flat() as number[]

		if (shareTypes.length > 0
			|| (node.owner !== getCurrentUser()?.uid || isExternal(node))) {
			return t('files_sharing', 'Shared')
		}

		return ''
	},

	title({ nodes }) {
		const node = nodes[0]!
		if (node.owner && (node.owner !== getCurrentUser()?.uid || isExternal(node))) {
			const ownerDisplayName = node?.attributes?.['owner-display-name']
			return t('files_sharing', 'Shared by {ownerDisplayName}', { ownerDisplayName })
		}

		const shareTypes = Object.values(node?.attributes?.['share-types'] || {}).flat() as number[]
		if (shareTypes.length > 1) {
			return t('files_sharing', 'Shared multiple times with different people')
		}

		const sharees = node.attributes.sharees?.sharee as { id: string, 'display-name': string, type: ShareType }[] | undefined
		if (!sharees) {
			// No sharees so just show the default message to create a new share
			return t('files_sharing', 'Sharing options')
		}

		const sharee = [sharees].flat()[0] // the property is sometimes weirdly normalized, so we need to compensate
		switch (sharee?.type) {
			case ShareType.User:
				return t('files_sharing', 'Shared with {user}', { user: sharee['display-name'] })
			case ShareType.Group:
				return t('files_sharing', 'Shared with group {group}', { group: sharee['display-name'] ?? sharee.id })
			default:
				return t('files_sharing', 'Shared with others')
		}
	},

	iconSvgInline({ nodes }) {
		const node = nodes[0]!
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
		if (shareTypes.includes(ShareType.Group)
			|| shareTypes.includes(ShareType.RemoteGroup)) {
			return AccountGroupSvg
		}

		// Circle shares
		if (shareTypes.includes(ShareType.Team)) {
			return CircleSvg
		}

		if (node.owner && (node.owner !== getCurrentUser()?.uid || isExternal(node))) {
			return generateAvatarSvg(node.owner, isExternal(node))
		}

		return AccountPlusSvg
	},

	enabled({ nodes }) {
		if (nodes.length !== 1) {
			return false
		}

		// Do not leak information about users to public shares
		if (isPublicShare()) {
			return false
		}

		const node = nodes[0]!
		const shareTypes = node.attributes?.['share-types']
		const isMixed = Array.isArray(shareTypes) && shareTypes.length > 0

		// If the node is shared multiple times with
		// different share types to the current user
		if (isMixed) {
			return true
		}

		// If the node is shared by someone else
		if (node.owner !== getCurrentUser()?.uid || isExternal(node)) {
			return true
		}

		// You need share permissions to share this file
		// and read permissions to see the sidebar
		return (node.permissions & Permission.SHARE) !== 0
			&& (node.permissions & Permission.READ) !== 0
	},

	async exec({ nodes, view, folder, contents }) {
		// You need read permissions to see the sidebar
		const node = nodes[0]
		if ((node.permissions & Permission.READ) !== 0) {
			window.OCA?.Files?.Sidebar?.setActiveTab?.('sharing')
			sidebarAction.exec({ nodes, view, folder, contents })
			return null
		}

		// Should not happen as the enabled check should prevent this
		// leaving it here for safety or in case someone calls this action directly
		showError(t('files_sharing', 'You do not have enough permissions to share this file.'))
		return null
	},

	inline: () => true,

})

registerFileAction(action)
