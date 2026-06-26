/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'

/**
 * Set the page heading
 *
 * @param {string} heading page title from the history api
 * @since 27.0.0
 */
export function setPageHeading(heading) {
	const headingEl = document.getElementById('page-heading-level-1')
	if (headingEl) {
		headingEl.textContent = heading
	}
}
export default {
	/**
	 * @return {boolean} Whether the user opted-out of shortcuts so that they should not be registered
	 */
	disableKeyboardShortcuts() {
		return loadState('theming', 'shortcutsDisabled', false)
	},
	setPageHeading,
}
