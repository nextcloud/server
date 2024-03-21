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

import type { Entry } from '@nextcloud/files'
import type { ComponentInstance } from 'vue'
import type { TemplateFile } from '../types.ts'

import { Folder, Node, Permission, addNewFileMenuEntry } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { newNodeName } from '../utils/newNodeDialog'
import { translate as t } from '@nextcloud/l10n'
import Vue, { defineAsyncComponent } from 'vue'

// async to reduce bundle size
const TemplatePickerVue = defineAsyncComponent(() => import('../views/TemplatePicker.vue'))
let TemplatePicker: ComponentInstance & { open: (n: string, t: TemplateFile) => void } | null = null

const getTemplatePicker = async () => {
	if (TemplatePicker === null) {
		// Create document root
		const mountingPoint = document.createElement('div')
		mountingPoint.id = 'template-picker'
		document.body.appendChild(mountingPoint)

		// Init vue app
		TemplatePicker = new Vue({
			render: (h) => h(TemplatePickerVue, { ref: 'picker' }),
			methods: { open(...args) { this.$refs.picker.open(...args) } },
			el: mountingPoint,
		})
	}
	return TemplatePicker
}

/**
 * Register all new-file-menu entries for all template providers
 */
export function registerTemplateEntries() {
	const templates = loadState<TemplateFile[]>('files', 'templates', [])

	// Init template files menu
	templates.forEach((provider, index) => {
		addNewFileMenuEntry({
			id: `template-new-${provider.app}-${index}`,
			displayName: provider.label,
			iconClass: provider.iconClass || 'icon-file',
			iconSvgInline: provider.iconSvgInline,
			enabled(context: Folder): boolean {
				return (context.permissions & Permission.CREATE) !== 0
			},
			order: 11,
			async handler(context: Folder, content: Node[]) {
				const templatePicker = getTemplatePicker()
				const name = await newNodeName(`${provider.label}${provider.extension}`, content, {
					label: t('files', 'Filename'),
					name: provider.label,
				})

				if (name !== null) {
					// Create the file
					const picker = await templatePicker
					picker.open(name, provider)
				}
			},
		} as Entry)
	})
}
