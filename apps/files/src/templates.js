/**
 * @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { getLoggerBuilder } from '@nextcloud/logger'
import { loadState } from '@nextcloud/initial-state'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import Vue from 'vue'

import TemplatePickerView from './views/TemplatePicker'

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
const templates = loadState('files', 'templates', [])
logger.debug('Templates providers', templates)

// Init vue app
const View = Vue.extend(TemplatePickerView)
const TemplatePicker = new View({
	name: 'TemplatePicker',
	propsData: {
		logger,
	},
})
TemplatePicker.$mount('#template-picker')

// Init template engine after load
window.addEventListener('DOMContentLoaded', function() {
	// Init template files menu
	templates.forEach((provider, index) => {

		const newTemplatePlugin = {
			attach(menu) {
				const fileList = menu.fileList

				// only attach to main file list, public view is not supported yet
				if (fileList.id !== 'files' && fileList.id !== 'files.public') {
					return
				}

				// register the new menu entry
				menu.addMenuEntry({
					id: `template-new-${provider.app}-${index}`,
					displayName: provider.label,
					templateName: provider.label + provider.extension,
					iconClass: provider.iconClass || 'icon-file',
					fileType: 'file',
					actionHandler(name) {
						TemplatePicker.open(name, provider)
					},
				})
			},
		}
		OC.Plugins.register('OCA.Files.NewFileMenu', newTemplatePlugin)
	})
})
