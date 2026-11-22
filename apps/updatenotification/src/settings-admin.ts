/*!
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import AdminSettingsSection from './views/AdminSettingsSection.vue'

import 'vite/modulepreload-polyfill'

const app = createApp(AdminSettingsSection)
app.mount('#updatenotification')
