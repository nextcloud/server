/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia } from 'pinia'
import { createApp } from 'vue'
import PersonalSettings from './views/PersonalSettings.vue'

const pinia = createPinia()
const app = createApp(PersonalSettings)
app.use(pinia)
app.mount('#twofactor-backupcodes-settings')
