/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getCSPNonce } from '@nextcloud/auth'
import { t, n } from '@nextcloud/l10n'

import Vue from 'vue'
import PasswordSection from './components/PasswordSection.vue'

// eslint-disable-next-line camelcase
__webpack_nonce__ = getCSPNonce()

Vue.prototype.t = t
Vue.prototype.n = n

export default new Vue({
	el: '#security-password',
	name: 'main-personal-password',
	render: h => h(PasswordSection),
})
