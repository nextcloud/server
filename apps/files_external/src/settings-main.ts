/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import FilesExternalApp from './views/FilesExternalSettings.vue'

const app = createApp(FilesExternalApp)
app.mount('#files-external')
