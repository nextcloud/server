/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia } from 'pinia'
import { createApp } from 'vue'
import AppstoreApp from './AppstoreApp.vue'
import router from './router/index.ts'

import 'vite/modulepreload-polyfill'

const pinia = createPinia()
const app = createApp(AppstoreApp)
app.use(pinia)
app.use(router)
app.mount('#content')
