/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import CalDavSettings from './views/CalDavSettings.vue'

const app = createApp(CalDavSettings)
app.mount('#settings-admin-caldav')
