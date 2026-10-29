/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import PublicPageUserMenu from './views/PublicPageUserMenu.vue'
import { mountInPlace } from './utils/mountInPlace.ts'

const placeholder = document.getElementById('public-page-user-menu')
if (placeholder) {
	mountInPlace(createApp(PublicPageUserMenu), placeholder)
}
