/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import ExampleContentSettingsSection from './views/ExampleContentSettingsSection.vue'

const app = createApp(ExampleContentSettingsSection)
app.mount('#settings-example-content')
