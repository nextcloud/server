/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import SettingsApp from './SettingsApp.vue'

// mounting replaces everything in #content, so take the page heading and the server-rendered sections out first
const sections = document.getElementById('original-settings-content')!
sections.prepend(document.getElementById('page-heading-level-1')!)
sections.remove()
createApp(SettingsApp, { sections }).mount('#content')
