/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'

import SettingsPresets from './views/SettingsPresets.vue'
import { getCSPNonce } from '@nextcloud/auth'

// CSP config for webpack dynamic chunk loading
// eslint-disable-next-line camelcase
__webpack_nonce__ = getCSPNonce()

export default new Vue({
	render: h => h(SettingsPresets),
	el: '#settings-presets',
	name: 'SettingsPresets',
})
