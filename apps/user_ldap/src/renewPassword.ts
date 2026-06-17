/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import RenewPasswordView from './views/RenewPassword.vue'

const app = createApp(RenewPasswordView)
app.mount('#user_ldap-renewPassword')
