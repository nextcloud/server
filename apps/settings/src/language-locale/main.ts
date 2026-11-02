/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'
import { createApp } from 'vue'
import LanguageLocaleSettings from './LanguageLocaleSettings.vue'

const app = createApp(LanguageLocaleSettings)
app.mixin({ methods: { t } })
app.mount('#language-locale-settings')
