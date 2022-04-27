/**
 * @copyright 2021, Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
import { getRequestToken } from '@nextcloud/auth'
import { translate as t } from '@nextcloud/l10n'
import VTooltip from 'v-tooltip'

import logger from './logger'

import Profile from './views/Profile'

__webpack_nonce__ = btoa(getRequestToken())

Vue.use(VTooltip)

Vue.mixin({
	props: {
		logger,
	},
	methods: {
		t,
	},
})

const View = Vue.extend(Profile)

window.addEventListener('DOMContentLoaded', () => {
	new View().$mount('#vue-profile')
})
