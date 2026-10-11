/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia } from 'pinia'
import { createApp } from 'vue'
import PreviewsSettings from './components/PreviewsSettings.vue'

const app = createApp(PreviewsSettings)
app.use(createPinia())
app.mount('#settings-admin-previews')
