/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { t } from '@nextcloud/l10n'
import { addPasswordConfirmationInterceptors } from '@nextcloud/password-confirmation'
import { createApp } from 'vue'
import AdminDelegating from './AdminDelegating.vue'

addPasswordConfirmationInterceptors(axios)

const app = createApp(AdminDelegating)
app.mixin({ methods: { t } })
app.mount('#admin-right-sub-granting')
