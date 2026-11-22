/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'
import { ShareType } from '@nextcloud/sharing'

/**
 * Whether the source name should be shown.
 * It is set when any sharer in between renamed the node.
 *
 * @param {string} originalDisplayName
 */
function wasRenamed(originalDisplayName) {
	return typeof originalDisplayName === 'string' && originalDisplayName !== ''
}

/**
 *
 * @param share
 */
function shareWithTitle(share) {
	if (share.type === ShareType.Group) {
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
		return t(
			'files_sharing',
			'Shared with you by {owner}',
			{ owner: share.ownerDisplayName },
			undefined,
			{ escape: false },
		)
	}
}

/**
 * Source name of a received share, shown on its own line.
 * Empty when no sharer in the chain renamed the node.
 *
 * @param {string} [originalDisplayName]
 * @return {string}
 */
function originalShareLabel(originalDisplayName = '') {
	if (!wasRenamed(originalDisplayName)) {
		return ''
	}
	return t(
		'files_sharing',
		'Originally as {name}',
		{ name: originalDisplayName },
		undefined,
		{ escape: false },
	)
}

export { originalShareLabel, shareWithTitle }
