/*!
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import AdminTheming from './views/AdminTheming.vue'

import 'vite/modulepreload-polyfill'

const app = createApp(AdminTheming)
app.config.idPrefix = 'settings'
app.mount('#settings-admin-theming')
