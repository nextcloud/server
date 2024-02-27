/**
 * @copyright 2020, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
import { loadState } from '@nextcloud/initial-state'

import WebAuthnSection from './components/WebAuthn/Section.vue'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(OC.requestToken)

Vue.prototype.t = t

const View = Vue.extend(WebAuthnSection)
const devices = loadState('settings', 'webauthn-devices')
new View({
	propsData: {
		initialDevices: devices,
		isHttps: window.location.protocol === 'https:',
		isLocalhost: window.location.hostname === 'localhost',
		hasPublicKeyCredential: typeof (window.PublicKeyCredential) !== 'undefined',
	},
}).$mount('#security-webauthn')
