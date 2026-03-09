/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Permission, type Node } from '@nextcloud/files'
import { generateUrl } from '@nextcloud/router'
import { type LockState, LockType } from './types'
import { translate as t } from '@nextcloud/l10n'
import { getCurrentUser } from '@nextcloud/auth'

export const getLockStateFromAttributes = (node: Node): LockState => {
	return {
		isLocked: !!node.attributes.lock,
		lockOwner: node.attributes['lock-owner'],
		lockOwnerDisplayName: node.attributes['lock-owner-displayname'],
		lockOwnerType: parseInt(node.attributes['lock-owner-type']),
		lockOwnerEditor: node.attributes['lock-owner-editor'],
		lockTime: parseInt(node.attributes['lock-time']),
	}
}

export const canLock = (node: Node): boolean => {
	const state = getLockStateFromAttributes(node)

	if (!state.isLocked && isUpdatable(node)) {
		return true
	}

	return false
}

export const canUnlock = (node: Node): boolean => {
	const state = getLockStateFromAttributes(node)

	if (!state.isLocked) {
		return false
	}

	// File owners can always unlock any lock on their files
	if (node.owner === getCurrentUser()?.uid) {
		return true
	}

	if (!isUpdatable(node)) {
		return false
	}

	if (state.lockOwnerType === LockType.User && state.lockOwner === getCurrentUser()?.uid) {
		return true
	}

	if (state.lockOwnerType === LockType.Token && state.lockOwner === getCurrentUser()?.uid) {
		return true
	}

	return false
}

export const generateAvatarSvg = (userId: string) => {
	const avatarUrl = generateUrl('/avatar/{userId}/32', { userId })
	return `<svg width="32" height="32" viewBox="0 0 32 32"
		xmlns="http://www.w3.org/2000/svg" class="sharing-status__avatar">
		<image href="${avatarUrl}" height="32" width="32" />
	</svg>`
}

export const getInfoLabel = (node: Node): string => {
	const state = getLockStateFromAttributes(node)

	if (state.lockOwnerType === LockType.User) {
		return state.isLocked
			? t('files_lock', 'Manually locked by {user}', { user: state.lockOwnerDisplayName })
			: ''

	} else if (state.lockOwnerType === LockType.App) {
		return state.isLocked
			? t('files_lock', 'Locked by editing online in {app}', { app: state.lockOwnerDisplayName })
			: ''
	} else {
		return state.isLocked
			? t('files_lock', 'Automatically locked by {user}', { user: state.lockOwnerDisplayName })
			: ''
	}

	return ''
}

export const isUpdatable = (node: Node): boolean => {
	return (node.permissions & Permission.UPDATE) !== 0 && (node.attributes['share-permissions'] & Permission.UPDATE) !== 0
}
