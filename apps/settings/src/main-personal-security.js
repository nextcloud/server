/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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
import VTooltip from 'v-tooltip'

import AuthTokenSection from './components/AuthTokenSection.vue'
import { getRequestToken } from '@nextcloud/auth'
import { PiniaVuePlugin, createPinia } from 'pinia'

import '@nextcloud/password-confirmation/dist/style.css'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken())

const pinia = createPinia()

Vue.use(PiniaVuePlugin)
Vue.use(VTooltip, { defaultHtml: false })
Vue.prototype.t = t

const View = Vue.extend(AuthTokenSection)
new View({ pinia }).$mount('#security-authtokens')
