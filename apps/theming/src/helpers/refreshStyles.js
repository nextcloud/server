/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Refresh server-side generated theming CSS
 * This resolves when all themes are reloaded
 */
export async function refreshStyles() {
	const themes = [...document.head.querySelectorAll('link.theme')]
	const promises = themes.map((theme) => new Promise((resolve) => {
		const url = new URL(theme.href)
		url.searchParams.set('v', Date.now())
		const newTheme = theme.cloneNode()
		newTheme.href = url.toString()
		newTheme.onload = () => {
			theme.remove()
			resolve()
		}
		document.head.append(newTheme)
	}))

	// Wait until all themes are loaded
	await Promise.allSettled(promises)
}
