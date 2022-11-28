/**
 * @copyright 2022 Carl Schwa <carl@carlschwan.eu>
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
import Vue from 'vue'

import MailDeliverySettings from './components/MailDeliverySettings.vue'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(OC.requestToken)

Vue.prototype.t = t

// Not used here but required for legacy templates
window.OC = window.OC || {}
window.OC.Settings = window.OC.Settings || {}

const View = Vue.extend(MailDeliverySettings)
new View().$mount('#mail_delivery_settings')
