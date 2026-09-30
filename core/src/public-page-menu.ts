/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import PublicPageMenu from './views/PublicPageMenu.vue'
import { mountInPlace } from './utils/mountInPlace.ts'

const placeholder = document.getElementById('public-page-menu')
if (placeholder) {
	mountInPlace(createApp(PublicPageMenu), placeholder)
}
