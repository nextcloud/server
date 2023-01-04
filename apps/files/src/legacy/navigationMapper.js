/**
 * @copyright Copyright (c) 2022 John Molakvoæ <skjnldsv@protonmail.com>
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

import { loadState } from '@nextcloud/initial-state'
import logger from '../logger.js'

/**
 * Fetch and register the legacy files views
 */
export default function() {
	const legacyViews = Object.values(loadState('files', 'navigation', {}))

	if (legacyViews.length > 0) {
		logger.debug('Legacy files views detected. Processing...', legacyViews)
		legacyViews.forEach(view => {
			registerLegacyView(view)
			if (view.sublist) {
				view.sublist.forEach(subview => registerLegacyView({ ...subview, parent: view.id }))
			}
		})
	}
}

const registerLegacyView = function({ id, name, order, icon, parent, classes = '', expanded, params }) {
	OCP.Files.Navigation.register({
		id,
		name,
		order,
		params,
		parent,
		expanded: expanded === true,
		iconClass: icon ? `icon-${icon}` : 'nav-icon-' + id,
		legacy: true,
		sticky: classes.includes('pinned'),
	})
}
