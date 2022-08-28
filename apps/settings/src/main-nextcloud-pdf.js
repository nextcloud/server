/**
 * @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Jan C. Borchardt <hey@jancborchardt.net>
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

const hasPdf = loadState('settings', 'has-reasons-use-nextcloud-pdf') === true

window.addEventListener('DOMContentLoaded', function() {
	const link = document.getElementById('open-reasons-use-nextcloud-pdf')
	if (link && hasPdf) {
		link.addEventListener('click', function(event) {
			event.preventDefault()
			OCA.Viewer.open({
				path: '/Reasons to use Nextcloud.pdf',
			})
		})
	}
})
