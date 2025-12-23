/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import SystemTagsSection from './views/SystemTagsSection.vue'

import './css/fileEntryInlineSystemTags.scss'

createApp(SystemTagsSection)
	.mount('#vue-admin-systemtags')
