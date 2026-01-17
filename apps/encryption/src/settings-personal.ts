/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import SettingsPersonal from './views/SettingsPersonal.vue'

const app = createApp(SettingsPersonal)
app.mount('#encryption-settings-section')
