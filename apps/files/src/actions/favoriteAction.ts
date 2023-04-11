/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
import { emit } from '@nextcloud/event-bus'
import { translate as t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'
import Star from '@mdi/svg/svg/star.svg?raw'
import type { Node } from '@nextcloud/files'

import { generateUrl } from '@nextcloud/router'
import { registerFileAction, FileAction } from '../services/FileAction.ts'
import logger from '../logger.js'
import type { Navigation } from '../services/Navigation'

/**
 * If any of the nodes is not favorited
 * we display the favorite action.
 */
const shouldFavorite = (nodes: Node[]): boolean => {
	return nodes.some(node => node.attributes.favorite === 0)
}

registerFileAction(new FileAction({
	id: 'favorite',
	displayName(nodes: Node[]) {
		return shouldFavorite(nodes)
			? t('files', 'Add to favorites')
			: t('files', 'Remove from favorites')
	},
	iconSvgInline: () => Star,

	enabled(nodes: Node[]) {
		// We can only favorite nodes within files
		return !nodes.some(node => !node.root?.startsWith?.('/files'))
	},

	async exec(node: Node, view: Navigation) {
		const willFavorite = shouldFavorite([node])
		try {
			// TODO: migrate to webdav tags plugin
			const url = generateUrl('/apps/files/api/v1/files/') + node.path
			await axios.post(url, {
				tags: willFavorite
					? [OC.TAG_FAVORITE]
					: [],
			})

			// Let's delete even if we are in the favourites view
			// AND if it is removed from the user favorites
			// AND it's in the root of the favorites view
			if (view.id === 'favorites' && !willFavorite && node.dirname === '/') {
				emit('files:node:deleted', node)
			}

			// Update the node webdav attribute
			node.attributes.favorite = willFavorite ? 1 : 0

			// Dispatch event to whoever is interested
			if (willFavorite) {
				emit('files:favorites:added', node)
			} else {
				emit('files:favorites:removed', node)
			}

			return true
		} catch (error) {
			const action = willFavorite ? 'adding a file to favourites' : 'removing a file from favourites'
			logger.error('Error while ' + action, { error, source: node.source, node })
			return false
		}
	},
	async execBatch(nodes: Node[], view: Navigation) {
		return Promise.all(nodes.map(node => this.exec(node, view)))
	},

	order: 0,
}))
