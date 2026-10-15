/*!
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import UserTheming from './views/UserTheming.vue'

import 'vite/modulepreload-polyfill'

const app = createApp(UserTheming)
app.config.idPrefix = 'settings'
app.mount('#settings-personal-theming')
