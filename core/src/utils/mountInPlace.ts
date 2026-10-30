/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { App, ComponentPublicInstance } from 'vue'

/**
 * Mount an app in place of a placeholder element of the page template.
 *
 * The root components carry the id and landmark of the placeholder they
 * replace, so leaving the placeholder around would duplicate both. The app is
 * mounted while the placeholder is still part of the document, so its mount
 * hooks see a connected tree.
 *
 * @param app - The app to mount
 * @param placeholder - The element the app takes the place of
 */
export function mountInPlace(app: App, placeholder: Element): ComponentPublicInstance {
	const instance = app.mount(placeholder)
	placeholder.replaceWith(...placeholder.childNodes)
	return instance
}
