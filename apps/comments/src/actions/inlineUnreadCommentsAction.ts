/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import CommentProcessingSvg from '@mdi/svg/svg/comment-processing.svg?raw'
import { FileAction } from '@nextcloud/files'
import { n, t } from '@nextcloud/l10n'
import logger from '../logger.js'

export const action = new FileAction({
	id: 'comments-unread',

	title({ nodes }) {
		const unread = nodes[0]?.attributes['comments-unread'] as number | undefined
		if (typeof unread === 'number' && unread >= 0) {
			return n('comments', '1 new comment', '{unread} new comments', unread, { unread })
		}
		return t('comments', 'Comment')
	},

	// Empty string when rendered inline
	displayName: () => '',

	iconSvgInline: () => CommentProcessingSvg,

	enabled({ nodes }) {
		const unread = nodes[0]?.attributes?.['comments-unread'] as number | undefined
		return typeof unread === 'number' && unread > 0
	},

	async exec({ nodes }) {
		if (nodes.length !== 1 || !nodes[0]) {
			return false
		}

		try {
			window.OCA.Files.Sidebar.setActiveTab('comments')
			await window.OCA.Files.Sidebar.open(nodes[0].path)
			return null
		} catch (error) {
			logger.error('Error while opening sidebar', { error })
			return false
		}
	},

	inline: () => true,

	order: -140,
})
