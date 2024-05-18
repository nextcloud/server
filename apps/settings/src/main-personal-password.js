/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
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

import PasswordSection from './components/PasswordSection.vue'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(OC.requestToken)

Vue.prototype.t = t
Vue.prototype.n = n

export default new Vue({
	el: '#security-password',
	name: 'main-personal-password',
	render: h => h(PasswordSection),
})
