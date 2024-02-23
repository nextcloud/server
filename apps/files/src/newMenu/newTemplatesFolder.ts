/**
 * @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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
import type { Entry, Folder, Node } from '@nextcloud/files'

import { getCurrentUser } from '@nextcloud/auth'
import { showError } from '@nextcloud/dialogs'
import { Permission, removeNewFileMenuEntry } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { join } from 'path'
import { newNodeName } from '../utils/newNodeDialog'

import PlusSvg from '@mdi/svg/svg/plus.svg?raw'
import axios from '@nextcloud/axios'
import logger from '../logger.js'

let templatesPath = loadState<string|false>('files', 'templates_path', false)
logger.debug('Initial templates folder', { templatesPath })

/**
 * Init template folder
 * @param directory Folder where to create the templates folder
 * @param name Name to use or the templates folder
 */
const initTemplatesFolder = async function(directory: Folder, name: string) {
	const templatePath = join(directory.path, name)
	try {
		logger.debug('Initializing the templates directory', { templatePath })
		const { data } = await axios.post(generateOcsUrl('apps/files/api/v1/templates/path'), {
			templatePath,
			copySystemTemplates: true,
		})

		// Go to template directory
		window.OCP.Files.Router.goToRoute(
			null, // use default route
			{ view: 'files', fileid: undefined },
			{ dir: templatePath },
		)

		logger.info('Created new templates folder', {
			...data.ocs.data,
		})
		templatesPath = data.ocs.data.templates_path as string
	} catch (error) {
		logger.error('Unable to initialize the templates directory')
		showError(t('files', 'Unable to initialize the templates directory'))
	}
}

export const entry = {
	id: 'template-picker',
	displayName: t('files', 'Create new templates folder'),
	iconSvgInline: PlusSvg,
	order: 10,
	enabled(context: Folder): boolean {
		// Templates folder already initialized
		if (templatesPath) {
			return false
		}
		// Allow creation on your own folders only
		if (context.owner !== getCurrentUser()?.uid) {
			return false
		}
		return (context.permissions & Permission.CREATE) !== 0
	},
	async handler(context: Folder, content: Node[]) {
		const name = await newNodeName(t('files', 'Templates'), content, { name: t('files', 'New template folder') })

		if (name !== null) {
			// Create the template folder
			initTemplatesFolder(context, name)

			// Remove the menu entry
			removeNewFileMenuEntry('template-picker')
		}
	},
} as Entry
