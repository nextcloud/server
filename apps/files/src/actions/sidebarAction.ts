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
import { Permission, type Node, View, FileAction, FileType } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import InformationSvg from '@mdi/svg/svg/information-variant.svg?raw'

import logger from '../logger.js'

export const ACTION_DETAILS = 'details'

export const action = new FileAction({
	id: ACTION_DETAILS,
	displayName: () => t('files', 'Open details'),
	iconSvgInline: () => InformationSvg,

	// Sidebar currently supports user folder only, /files/USER
	enabled: (nodes: Node[]) => {
		// Only works on single node
		if (nodes.length !== 1) {
			return false
		}

		if (!nodes[0]) {
			return false
		}

		// Only work if the sidebar is available
		if (!window?.OCA?.Files?.Sidebar) {
			return false
		}

		return (nodes[0].root?.startsWith('/files/') && nodes[0].permissions !== Permission.NONE) ?? false
	},

	async exec(node: Node, view: View, dir: string) {
		try {
			// TODO: migrate Sidebar to use a Node instead
			await window.OCA.Files.Sidebar.open(node.path)

			// Silently update current fileid
			window.OCP.Files.Router.goToRoute(
				null,
				{ view: view.id, fileid: node.fileid },
				{ dir },
				true,
			)

			return null
		} catch (error) {
			logger.error('Error while opening sidebar', { error })
			return false
		}
	},

	order: -50,
})
