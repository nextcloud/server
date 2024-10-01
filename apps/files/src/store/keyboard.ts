/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { defineStore } from 'pinia'
import Vue from 'vue'

/**
 * Observe various events and save the current
 * special keys states. Useful for checking the
 * current status of a key when executing a method.
 * @param {...any} args
 */
export const useKeyboardStore = function(...args) {
	const store = defineStore('keyboard', {
		state: () => ({
			altKey: false,
			ctrlKey: false,
			metaKey: false,
			shiftKey: false,
		}),

		actions: {
			onEvent(event: MouseEvent | KeyboardEvent) {
				if (!event) {
					event = window.event as MouseEvent | KeyboardEvent
				}
				Vue.set(this, 'altKey', !!event.altKey)
				Vue.set(this, 'ctrlKey', !!event.ctrlKey)
				Vue.set(this, 'metaKey', !!event.metaKey)
				Vue.set(this, 'shiftKey', !!event.shiftKey)
			},
		},
	})

	const keyboardStore = store(...args)
	// Make sure we only register the listeners once
	if (!keyboardStore._initialized) {
		window.addEventListener('keydown', keyboardStore.onEvent)
		window.addEventListener('keyup', keyboardStore.onEvent)
		window.addEventListener('mousemove', keyboardStore.onEvent)

		keyboardStore._initialized = true
	}

	return keyboardStore
}
