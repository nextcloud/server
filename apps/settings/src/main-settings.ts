/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import Vue from 'vue'
import SettingsApp from './views/SettingsApp.vue'

__webpack_nonce__ = getCSPNonce()

const app = new Vue(SettingsApp)
app.$mount('#settings-app')
