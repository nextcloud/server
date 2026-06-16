/*
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import ProfileApp from './views/ProfileApp.vue'
import ProfileSections from './services/ProfileSections.js'

import 'vite/modulepreload-polyfill'

window.OCA.Profile ??= {}
window.OCA.Profile.ProfileSections = new ProfileSections()

const app = createApp(ProfileApp)
app.mount('#content')
