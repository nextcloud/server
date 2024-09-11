/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ComponentPublicInstance, VueConstructor } from 'vue'

import { Folder, Header, registerFileListHeaders } from '@nextcloud/files'
import Vue from 'vue'

type IFilesHeaderNoteToRecipient = ComponentPublicInstance & { updateFolder: (folder: Folder) => void }

/**
 * Register the  "note to recipient" as a files list header
 */
export default function registerNoteToRecipient() {
	let FilesHeaderNoteToRecipient: VueConstructor
	let instance: IFilesHeaderNoteToRecipient

	registerFileListHeaders(new Header({
		id: 'note-to-recipient',
		order: 0,
		// Always if there is a note
		enabled: (folder: Folder) => Boolean(folder.attributes.note),
		// Update the root folder if needed
		updated: (folder: Folder) => {
			if (instance) {
				instance.updateFolder(folder)
			}
		},
		// render simply spawns the component
		render: async (el: HTMLElement, folder: Folder) => {
			if (FilesHeaderNoteToRecipient === undefined) {
				const { default: component } = await import('../views/FilesHeaderNoteToRecipient.vue')
				FilesHeaderNoteToRecipient = Vue.extend(component)
			}
			instance = new FilesHeaderNoteToRecipient().$mount(el) as unknown as IFilesHeaderNoteToRecipient
			instance.updateFolder(folder)
		},
	}))
}
