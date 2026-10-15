/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia } from 'pinia'
import { createApp } from 'vue'
import FilesExternalApp from './views/FilesExternalSettings.vue'

const pinia = createPinia()
const app = createApp(FilesExternalApp)
app.config.idPrefix = 'files-external'
app.use(pinia)
app.mount('#files-external')
