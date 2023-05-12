/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import Vue from 'vue'
import AppMenu from './AppMenu.vue'
export const setUp = () => {
	Vue.mixin({
		methods: {
			t,
			n,
		},
	})

	const container = document.getElementById('header-left__appmenu')
	if (!container) {
		// no container, possibly we're on a public page
		return
	}
	const AppMenuApp = Vue.extend(AppMenu)
	const appMenu = new AppMenuApp({}).$mount(container)

	Object.assign(OC, {
		setNavigationCounter(id, counter) {
			appMenu.setNavigationCounter(id, counter)
		},
	})

}
