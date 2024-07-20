/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	},
}).$mount('#security-webauthn')
