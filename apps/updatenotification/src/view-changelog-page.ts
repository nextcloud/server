/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import ViewChangelogPage from './views/ViewChangelogPage.vue'

import 'vite/modulepreload-polyfill'

const app = createApp(ViewChangelogPage)
app.mount('#content')
