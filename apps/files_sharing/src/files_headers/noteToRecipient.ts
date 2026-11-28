/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder } from '@nextcloud/files'
import type { App } from 'vue'
import type FilesHeaderNoteToRecipient from '../views/FilesHeaderNoteToRecipient.vue'

import { registerFileListHeader } from '@nextcloud/files'
import { createApp } from 'vue'

/**
 * Register the  "note to recipient" as a files list header
 */
export default function registerNoteToRecipient() {
	let app: App
	let headerComponent: typeof FilesHeaderNoteToRecipient
	let instance: InstanceType<typeof FilesHeaderNoteToRecipient>

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
			if (app) {
				app.unmount()
			} else {
				headerComponent = (await import('../views/FilesHeaderNoteToRecipient.vue')).default
			}
			app = createApp(headerComponent)
			instance = app.mount(el) as InstanceType<typeof FilesHeaderNoteToRecipient>
			instance.updateFolder(folder)
		},
	})
}
