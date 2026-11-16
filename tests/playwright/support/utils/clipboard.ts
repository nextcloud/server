/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator } from '@playwright/test'

// A valid 1x1 pixel PNG image.
export const PNG_CONTENT = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC'

/**
 * Simulate pasting image files from the clipboard.
 *
 * @param target - The element receiving the paste event
 * @param filenames - The image filenames
 */
export async function pasteImages(target: Locator, filenames: string[]): Promise<boolean> {
	return target.evaluate((element, { filenames, content }) => {
		const dataTransfer = new DataTransfer()
		const bytes = Uint8Array.from(atob(content), (character) => character.charCodeAt(0))

		for (const name of filenames) {
			dataTransfer.items.add(new File([bytes], name, {
				type: 'image/png',
			}))
		}

		const event = new ClipboardEvent('paste', {
			bubbles: true,
			cancelable: true,
			clipboardData: dataTransfer,
		})

		element.dispatchEvent(event)

		return event.defaultPrevented
	}, { filenames, content: PNG_CONTENT })
}
