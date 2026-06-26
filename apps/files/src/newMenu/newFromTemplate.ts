/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder, INode, NewMenuEntry } from '@nextcloud/files'
import type { ComponentInstance } from 'vue'
import type { TemplateFile } from '../types.ts'

import { addNewFileMenuEntry, Permission } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { isPublicShare } from '@nextcloud/sharing/public'
import Vue, { defineAsyncComponent } from 'vue'
import { newNodeName } from '../utils/newNodeDialog.ts'

// async to reduce bundle size
const TemplatePickerVue = defineAsyncComponent(() => import('../views/TemplatePicker.vue'))
let TemplatePicker: ComponentInstance & { open: (n: string, t: TemplateFile) => void } | null = null

/**
 *
 * @param context
 */
async function getTemplatePicker(context: IFolder) {
	if (TemplatePicker === null) {
		// Create document root
		const mountingPoint = document.createElement('div')
		mountingPoint.id = 'template-picker'
		document.body.appendChild(mountingPoint)

		// Init vue app
		TemplatePicker = new Vue({
			render: (h) => h(
				TemplatePickerVue,
				{
					ref: 'picker',
					props: {
						parent: context,
					},
				},
			),
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
	let templates: TemplateFile[]
	if (isPublicShare()) {
		templates = loadState<TemplateFile[]>('files_sharing', 'templates', [])
	} else {
		templates = loadState<TemplateFile[]>('files', 'templates', [])
	}

	// Init template files menu
	templates.forEach((provider, index) => {
		addNewFileMenuEntry({
			id: `template-new-${provider.app}-${index}`,
			displayName: provider.label,
			iconSvgInline: provider.iconSvgInline,
			enabled(context: IFolder): boolean {
				if (context.attributes['is-encrypted']) {
					return false
				}

				// templates are only supported in folders where the user has read and create permissions
				return (context.permissions & Permission.READ) !== 0
					&& (context.permissions & Permission.CREATE) !== 0
			},
			order: 11,
			async handler(context: IFolder, content: INode[]) {
				const templatePicker = getTemplatePicker(context)
				const name = await newNodeName(`${provider.label}${provider.extension}`, content, {
					label: t('files', 'Filename'),
					name: provider.label,
				})

				if (name !== null) {
					// Create the file
					const picker = await templatePicker
					picker.open(name.trim(), provider)
				}
			},
		} satisfies NewMenuEntry)
	})
}
