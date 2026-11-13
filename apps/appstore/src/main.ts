/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import AppstoreApp from './AppstoreApp.vue'
import router from './router/index.ts'
import { pinia } from './store/pinia.ts'

import 'vite/modulepreload-polyfill'

const app = createApp(AppstoreApp)
app.config.idPrefix = 'appstore'
app.use(pinia)
app.use(router)
app.mount('#content')
