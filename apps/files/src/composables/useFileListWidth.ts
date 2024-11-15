/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Ref } from 'vue'
import { onMounted, readonly, ref } from 'vue'

// Currently observed element
let element: HTMLElement | undefined
// Reactive width
const width = ref(0)
// The resize observer for the file list
const observer = new ResizeObserver(([el]) => {
	width.value = el.contentRect.width
})

/**
 * Get the reactive width of the file list
 */
export function useFileListWidth(): Readonly<Ref<number>> {
	onMounted(() => {
		// Check if the element for the file list has changed
		// this can only happen if this composable is used within the files root app
		// or the root app was recreated for some reason
		const el = document.querySelector<HTMLElement>('#app-content-vue') ?? document.body
		// If the element changed (or initial call) we need to observe it
		if (el !== element) {
			observer.observe(el)
			// If there was a previous element we need to unobserve it
			if (element) {
				observer.unobserve(element)
			}
			element = el
		}
	})

	return readonly(width)
}
