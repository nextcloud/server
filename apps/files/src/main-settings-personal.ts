/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'
import { createApp } from 'vue'
import PersonalSettings from './components/PersonalSettings.vue'

import 'vite/modulepreload-polyfill'

const app = createApp(PersonalSettings)
app.config.globalProperties.t = t
app.mount('#files-personal-settings')
