/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
import logger from '../logger.ts'

const templatesEnabled = loadState<boolean>('files', 'templates_enabled', true)
let templatesPath = loadState<string|false>('files', 'templates_path', false)
logger.debug('Templates folder enabled', { templatesEnabled })
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
	displayName: t('files', 'Create templates folder'),
	iconSvgInline: PlusSvg,
	order: 30,
	enabled(context: Folder): boolean {
		// Templates disabled or templates folder already initialized
		if (!templatesEnabled || templatesPath) {
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
