/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Christopher Ng <chrng8@gmail.com>
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

import Vue from 'vue'

import ContactsMenu from '../views/ContactsMenu.vue'

/**
 * @todo move to contacts menu code https://github.com/orgs/nextcloud/projects/31#card-21213129
 */
export const setUp = () => {
	const mountPoint = document.getElementById('contactsmenu')
	if (mountPoint) {
		// eslint-disable-next-line no-new
		new Vue({
			el: mountPoint,
			render: h => h(ContactsMenu),
		})
	}
}
