/**
 * @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
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
import type { Entry } from '@nextcloud/files'

import { Folder, Node, Permission, addNewFileMenuEntry, removeNewFileMenuEntry } from '@nextcloud/files'
import { generateOcsUrl } from '@nextcloud/router'
import { getLoggerBuilder } from '@nextcloud/logger'
import { join } from 'path'
import { loadState } from '@nextcloud/initial-state'
import { showError } from '@nextcloud/dialogs'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'
import Vue from 'vue'

import PlusSvg from '@mdi/svg/svg/plus.svg?raw'

import TemplatePickerView from './views/TemplatePicker.vue'
import { getUniqueName } from './newMenu/newFolder'
import { getCurrentUser } from '@nextcloud/auth'

// Set up logger
const logger = getLoggerBuilder()
	.setApp('files')
	.detectUser()
	.build()

// Add translates functions
Vue.mixin({
	methods: {
		t,
		n,
	},
})

// Create document root
const TemplatePickerRoot = document.createElement('div')
TemplatePickerRoot.id = 'template-picker'
document.body.appendChild(TemplatePickerRoot)

// Retrieve and init templates
let templates = loadState('files', 'templates', [])
let templatesPath = loadState('files', 'templates_path', false)
logger.debug('Templates providers', templates)
logger.debug('Templates folder', { templatesPath })

// Init vue app
const View = Vue.extend(TemplatePickerView)
const TemplatePicker = new View({
	name: 'TemplatePicker',
	propsData: {
		logger,
	},
})
TemplatePicker.$mount('#template-picker')
if (!templatesPath) {
	logger.debug('Templates folder not initialized')
	addNewFileMenuEntry({
		id: 'template-picker',
		displayName: t('files', 'Create new templates folder'),
		iconSvgInline: PlusSvg,
		order: 10,
		enabled(context: Folder): boolean {
			// Allow creation on your own folders only
			if (context.owner !== getCurrentUser()?.uid) {
				return false
			}
			return (context.permissions & Permission.CREATE) !== 0
		},
		handler(context: Folder, content: Node[]) {
			// Check for conflicts
			const contentNames = content.map((node: Node) => node.basename)
			const name = getUniqueName(t('files', 'Templates'), contentNames)

			// Create the template folder
			initTemplatesFolder(context, name)

			// Remove the menu entry
			removeNewFileMenuEntry('template-picker')
		},
	} as Entry)
}

// Init template files menu
templates.forEach((provider, index) => {
	addNewFileMenuEntry({
		id: `template-new-${provider.app}-${index}`,
		displayName: provider.label,
		// TODO: migrate to inline svg
		iconClass: provider.iconClass || 'icon-file',
		enabled(context: Folder): boolean {
			return (context.permissions & Permission.CREATE) !== 0
		},
		order: 11,
		handler(context: Folder, content: Node[]) {
			// Check for conflicts
			const contentNames = content.map((node: Node) => node.basename)
			const name = getUniqueName(provider.label + provider.extension, contentNames)

			// Create the file
			TemplatePicker.open(name, provider)
		},
	} as Entry)
})

// Init template folder
const initTemplatesFolder = async function(directory: Folder, name: string) {
	const templatePath = join(directory.path, name)
	try {
		logger.debug('Initializing the templates directory', { templatePath })
		const response = await axios.post(generateOcsUrl('apps/files/api/v1/templates/path'), {
			templatePath,
			copySystemTemplates: true,
		})

		// Go to template directory
		window.OCP.Files.Router.goToRoute(
			null, // use default route
			{ view: 'files', fileid: undefined },
			{ dir: templatePath },
		)

		templates = response.data.ocs.data.templates
		templatesPath = response.data.ocs.data.template_path
	} catch (error) {
		logger.error('Unable to initialize the templates directory')
		showError(t('files', 'Unable to initialize the templates directory'))
	}
}
