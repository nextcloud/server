/*!
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia } from 'pinia'
import { createApp } from 'vue'
import AuthTokenSection from './components/AuthTokenSection.vue'

const app = createApp(AuthTokenSection)
app.use(createPinia())
app.mount('#security-authtokens')
