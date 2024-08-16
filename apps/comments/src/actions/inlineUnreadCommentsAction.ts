/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { FileAction, Node } from '@nextcloud/files'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import CommentProcessingSvg from '@mdi/svg/svg/comment-processing.svg?raw'

import logger from '../logger'

export const action = new FileAction({
	id: 'comments-unread',

	title(nodes: Node[]) {
		const unread = nodes[0].attributes['comments-unread'] as number
		if (unread >= 0) {
			return n('comments', '1 new comment', '{unread} new comments', unread, { unread })
		}
		return t('comments', 'Comment')
	},

	// Empty string when rendered inline
	displayName: () => '',

	iconSvgInline: () => CommentProcessingSvg,

	enabled(nodes: Node[]) {
		const unread = nodes[0].attributes['comments-unread'] as number|undefined
		return typeof unread === 'number' && unread > 0
	},

	async exec(node: Node) {
		try {
			window.OCA.Files.Sidebar.setActiveTab('comments')
			await window.OCA.Files.Sidebar.open(node.path)
			return null
		} catch (error) {
			logger.error('Error while opening sidebar', { error })
			return false
		}
	},

	inline: () => true,

	order: -140,
})
