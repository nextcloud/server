/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
import { defineStore } from 'pinia'
import Vue from 'vue'

/**
 * Observe various events and save the current
 * special keys states. Useful for checking the
 * current status of a key when executing a method.
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
