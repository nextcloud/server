/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { n, t } from '@nextcloud/l10n'
import { createPinia } from 'pinia'
import { createApp } from 'vue'
import AuthTokenSection from './components/AuthTokenSection.vue'

const app = createApp(AuthTokenSection)
app.use(createPinia())
app.mixin({ methods: { t, n } })
app.mount('#security-authtokens')
