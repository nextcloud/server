/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder } from '@nextcloud/files'
import type { ComponentPublicInstance, VueConstructor } from 'vue'

import { registerFileListHeader } from '@nextcloud/files'
import Vue from 'vue'

type IFilesHeaderNoteToRecipient = ComponentPublicInstance & { updateFolder: (folder: IFolder) => void }

/**
 * Register the  "note to recipient" as a files list header
 */
export default function registerNoteToRecipient() {
	let FilesHeaderNoteToRecipient: VueConstructor
	let instance: IFilesHeaderNoteToRecipient

	registerFileListHeader({
		id: 'note-to-recipient',
		order: 0,
		// Always if there is a note
		enabled: (folder: IFolder) => Boolean(folder.attributes.note),
		// Update the root folder if needed
		updated: (folder: IFolder) => {
			if (instance) {
				instance.updateFolder(folder)
			}
		},
		// render simply spawns the component
		render: async (el: HTMLElement, folder: IFolder) => {
			if (FilesHeaderNoteToRecipient === undefined) {
				const { default: component } = await import('../views/FilesHeaderNoteToRecipient.vue')
				FilesHeaderNoteToRecipient = Vue.extend(component)
			}
			instance = new FilesHeaderNoteToRecipient().$mount(el) as unknown as IFilesHeaderNoteToRecipient
			instance.updateFolder(folder)
		},
	})
}
