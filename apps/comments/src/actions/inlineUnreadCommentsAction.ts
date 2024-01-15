/**
 * @copyright Copyright (c) 2023 Lucas Azevedo <lhs_azevedo@hotmail.com>
 *
 * @author Lucas Azevedo <lhs_azevedo@hotmail.com>
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
