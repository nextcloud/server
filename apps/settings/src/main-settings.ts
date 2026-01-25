/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import Vue from 'vue'
import SettingsNavigation from './views/SettingsNavigation.vue'

__webpack_nonce__ = getCSPNonce()

const app = new Vue(SettingsNavigation)
app.$mount('#app-navigation')
