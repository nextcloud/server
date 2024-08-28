/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Type as ShareTypes } from '@nextcloud/sharing'

const shareWithTitle = function(share) {
	if (share.type === ShareTypes.SHARE_TYPE_GROUP) {
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
	} else if (share.type === ShareTypes.SHARE_TYPE_CIRCLE) {
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
	} else if (share.type === ShareTypes.SHARE_TYPE_ROOM) {
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

export { shareWithTitle }
