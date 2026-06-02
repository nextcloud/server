/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFileAction } from '@nextcloud/files'

import CommentProcessingSvg from '@mdi/svg/svg/comment-processing.svg?raw'
import { getSidebar } from '@nextcloud/files'
import { n, t } from '@nextcloud/l10n'
import logger from '../logger.ts'
import { isUsingActivityIntegration } from '../utils/activity.ts'

export const action: IFileAction = {
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
			const sidebar = getSidebar()
			const sidebarTabId = isUsingActivityIntegration() ? 'activity' : 'comments'
			if (sidebar.isOpen && sidebar.node?.source === nodes[0].source) {
				logger.debug('Sidebar already open for this node, just activating comments tab')
				sidebar.setActiveTab(sidebarTabId)
				return null
			}
			sidebar.open(nodes[0], sidebarTabId)
			return null
		} catch (error) {
			logger.error('Error while opening sidebar', { error })
			return false
		}
	},

	inline: () => true,

	order: -140,
}
