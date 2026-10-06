/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { ShareType } from '@nextcloud/sharing'

/**
 * Whether the recipient renamed the share away from the source name.
 *
 * @param {string} originalDisplayName
 */
function wasRenamed(originalDisplayName) {
	return typeof originalDisplayName === 'string' && originalDisplayName !== ''
}

/**
 * Title for a share received by the current user.
 * The source name is included only when this user renamed the received share.
 *
 * @param {object} share
 * @param {string} [originalDisplayName]
 */
function shareWithTitle(share, originalDisplayName = '') {
	const renamed = wasRenamed(originalDisplayName)

	if (share.type === ShareType.Group) {
		if (renamed) {
			return t(
				'files_sharing',
				'Shared with you and the group {group} by {owner}, originally as {name}',
				{
					group: share.shareWithDisplayName,
					owner: share.ownerDisplayName,
					name: originalDisplayName,
				},
				undefined,
				{ escape: false },
			)
		}
		return t(
			'files_sharing',
			'Shared with you and the group {group} by {owner}',
			{
				group: share.shareWithDisplayName,
				owner: share.ownerDisplayName,
			},
			undefined,
			{ escape: false },
		)
	} else if (share.type === ShareType.Team) {
		if (renamed) {
			return t(
				'files_sharing',
				'Shared with you and {circle} by {owner}, originally as {name}',
				{
					circle: share.shareWithDisplayName,
					owner: share.ownerDisplayName,
					name: originalDisplayName,
				},
				undefined,
				{ escape: false },
			)
		}
		return t(
			'files_sharing',
			'Shared with you and {circle} by {owner}',
			{
				circle: share.shareWithDisplayName,
				owner: share.ownerDisplayName,
			},
			undefined,
			{ escape: false },
		)
	} else if (share.type === ShareType.Room) {
		if (share.shareWithDisplayName) {
			if (renamed) {
				return t(
					'files_sharing',
					'Shared with you and the conversation {conversation} by {owner}, originally as {name}',
					{
						conversation: share.shareWithDisplayName,
						owner: share.ownerDisplayName,
						name: originalDisplayName,
					},
					undefined,
					{ escape: false },
				)
			}
			return t(
				'files_sharing',
				'Shared with you and the conversation {conversation} by {owner}',
				{
					conversation: share.shareWithDisplayName,
					owner: share.ownerDisplayName,
				},
				undefined,
				{ escape: false },
			)
		} else {
			if (renamed) {
				return t(
					'files_sharing',
					'Shared with you in a conversation by {owner}, originally as {name}',
					{
						owner: share.ownerDisplayName,
						name: originalDisplayName,
					},
					undefined,
					{ escape: false },
				)
			}
			return t(
				'files_sharing',
				'Shared with you in a conversation by {owner}',
				{
					owner: share.ownerDisplayName,
				},
				undefined,
				{ escape: false },
			)
		}
	} else {
		if (renamed) {
			return t(
				'files_sharing',
				'Shared with you by {owner}, originally as {name}',
				{
					owner: share.ownerDisplayName,
					name: originalDisplayName,
				},
				undefined,
				{ escape: false },
			)
		} else {
			return t(
				'files_sharing',
				'Shared with you by {owner}',
				{ owner: share.ownerDisplayName },
				undefined,
				{ escape: false },
			)
		}
	}
}

export { shareWithTitle }
