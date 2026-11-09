/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { addPasswordConfirmationInterceptors } from '@nextcloud/password-confirmation'
import { createApp } from 'vue'
import AdminTwoFactor from './AdminTwoFactor.vue'
import EncryptionSettings from './encryption/EncryptionSettings.vue'

addPasswordConfirmationInterceptors(axios)

createApp(AdminTwoFactor).mount('#two-factor-auth-settings')
createApp(EncryptionSettings).mount('#vue-admin-encryption')
