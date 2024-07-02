import type { VueConstructor } from 'vue'

import { Header, registerFileListHeaders } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import Vue from 'vue'

const noteToRecipient = loadState<string>('files_sharing', 'noteToRecipient', '')

/**
 * Register the  "note to recipient" as a files list header
 */
export default function registerNoteToRecipient() {
	let FilesHeaderNoteToRecipient: VueConstructor

	registerFileListHeaders(new Header({
		id: 'note-to-recipient',
		order: 0,
		// Always if there is a note
		enabled: () => noteToRecipient !== '',
		// No need to update the note does not change
		updated: () => {},
		// render simply spawns the component
		render: async (el: HTMLElement) => {
			if (FilesHeaderNoteToRecipient === undefined) {
				const { default: component } = await import('../views/FilesHeaderNoteToRecipient.vue')
				FilesHeaderNoteToRecipient = Vue.extend(component)
			}
			const instance = new FilesHeaderNoteToRecipient({
				propsData: {
					noteToRecipient,
				},
			})
			instance.$mount(el)
		},
	}))
}
