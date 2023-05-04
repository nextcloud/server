/**
 * @copyright Copyright (c) 2022 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
