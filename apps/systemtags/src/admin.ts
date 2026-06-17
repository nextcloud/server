/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import SystemTagsSection from './views/SystemTagsSection.vue'

const app = createApp(SystemTagsSection)
app.mount('#vue-admin-systemtags')
