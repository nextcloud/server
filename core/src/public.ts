/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const body = document.body
const footer = document.querySelector('footer')
let prevHeight = footer?.offsetHeight

const onResize: ResizeObserverCallback = (entries) => {
	for (const entry of entries) {
		const height = entry.contentRect.height
		if (height === prevHeight) {
			return
		}
		prevHeight = height
		body.style.setProperty('--footer-height', `${height}px`)
	}
}

if (footer) {
	new ResizeObserver(onResize)
		.observe(footer, {
			box: 'border-box', // <footer> is border-box
		})
}
