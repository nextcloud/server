/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Ref } from 'vue'
import { onMounted, readonly, ref } from 'vue'

/** The element we observe */
let element: HTMLElement | undefined

/** The current width of the element */
const width = ref(0)

const observer = new ResizeObserver((elements) => {
	if (elements[0].contentBoxSize) {
		// use the newer `contentBoxSize` property if available
		width.value = elements[0].contentBoxSize[0].inlineSize
	} else {
		// fall back to `contentRect`
		width.value = elements[0].contentRect.width
	}
})

/**
 * Update the observed element if needed and reconfigure the observer
 */
function updateObserver() {
	const el = document.querySelector<HTMLElement>('#app-content-vue') ?? document.body
	if (el !== element) {
		// if already observing: stop observing the old element
		if (element) {
			observer.unobserve(element)
		}
		// observe the new element if needed
		observer.observe(el)
		element = el
	}
}

/**
 * Get the reactive width of the file list
 */
export function useFileListWidth(): Readonly<Ref<number>> {
	// Update the observer when the component is mounted (e.g. because this is the files app)
	onMounted(updateObserver)
	// Update the observer also in setup context, so we already have an initial value
	updateObserver()

	return readonly(width)
}
