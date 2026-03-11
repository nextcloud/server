/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { computed, onMounted, readonly, ref } from 'vue'

/** The element we observe */
let element: HTMLElement | undefined

/** The current width of the element */
const width = ref(0)

const isWide = computed(() => width.value >= 1024)
const isMedium = computed(() => width.value >= 512 && width.value < 1024)
const isNarrow = computed(() => width.value < 512)

const observer = new ResizeObserver(([element]) => {
	if (!element) {
		return
	}

	const contentBoxSize = element.contentBoxSize?.[0]
	if (contentBoxSize) {
		// use the newer `contentBoxSize` property if available
		width.value = contentBoxSize.inlineSize
	} else {
		// fall back to `contentRect`
		width.value = element.contentRect.width
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
export function useFileListWidth() {
	// Update the observer when the component is mounted (e.g. because this is the files app)
	onMounted(updateObserver)
	// Update the observer also in setup context, so we already have an initial value
	updateObserver()

	return {
		width: readonly(width),

		isWide,
		isMedium,
		isNarrow,
	}
}
