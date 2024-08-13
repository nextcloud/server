/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import { getCSPNonce } from '@nextcloud/auth'

import PersonalSettings from './components/PersonalSettings.vue'

// eslint-disable-next-line camelcase
__webpack_nonce__ = getCSPNonce()

Vue.prototype.t = t

if (!window.TESTING) {
	const View = Vue.extend(PersonalSettings)
	new View().$mount('#files-personal-settings')
}
