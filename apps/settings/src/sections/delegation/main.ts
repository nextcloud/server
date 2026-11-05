/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { addPasswordConfirmationInterceptors } from '@nextcloud/password-confirmation'
import { createApp } from 'vue'
import AdminDelegating from './AdminDelegating.vue'

addPasswordConfirmationInterceptors(axios)

createApp(AdminDelegating).mount('#admin-right-sub-granting')
