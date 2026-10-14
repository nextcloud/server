/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import LegacyUnifiedSearch from './views/LegacyUnifiedSearch.vue'
import { mountInPlace } from './utils/mountInPlace.ts'

const placeholder = document.getElementById('unified-search')
if (placeholder) {
	mountInPlace(createApp(LegacyUnifiedSearch), placeholder)
}
