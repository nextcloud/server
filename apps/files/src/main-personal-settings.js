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
const View = Vue.extend(PersonalSettings)
const instance = new View()
instance.$mount('#files-personal-settings')
