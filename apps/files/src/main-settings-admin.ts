/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import { t } from '@nextcloud/l10n'
import Vue from 'vue'
import PersonalSettings from './views/SettingsAdmin.vue'

__webpack_nonce__ = getCSPNonce()

Vue.prototype.t = t
const View = Vue.extend(PersonalSettings)
const instance = new View()
instance.$mount('#files-admin-settings')
