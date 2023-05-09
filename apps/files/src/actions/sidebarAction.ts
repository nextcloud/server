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
import { translate as t } from '@nextcloud/l10n'
import InformationSvg from '@mdi/svg/svg/information-variant.svg?raw'
import type { Node } from '@nextcloud/files'

import { registerFileAction, FileAction } from '../services/FileAction.ts'
import logger from '../logger.js'

export const ACTION_DETAILS = 'details'

registerFileAction(new FileAction({
	id: ACTION_DETAILS,
	displayName: () => t('files', 'Details'),
	iconSvgInline: () => InformationSvg,

	// Sidebar currently supports user folder only, /files/USER
	enabled: (files: Node[]) => !!window?.OCA?.Files?.Sidebar
		&& files.some(node => node.root?.startsWith('/files/')),

	async exec(node: Node) {
		try {
			// TODO: migrate Sidebar to use a Node instead
			window?.OCA?.Files?.Sidebar?.open?.(node.path)

			return null
		} catch (error) {
			logger.error('Error while opening sidebar', { error })
			return false
		}
	},

	default: true,
	order: -50,
}))
