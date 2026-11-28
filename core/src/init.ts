/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getLocale } from '@nextcloud/l10n'
import { setUp as setUpContactsMenu } from './components/ContactsMenu.ts'
import { setUp as setUpUserMenu } from './components/UserMenu.ts'
import { initSessionHeartBeat } from './session-heartbeat.ts'
import { initFallbackClipboardAPI } from './utils/ClipboardFallback.ts'
import { logger } from './utils/logger.ts'
import { loadMomentLocale } from './utils/momentLocale.ts'
import { interceptRequests } from './utils/xhr-request.js'

/**
 * Initializes core
 */
export function initCore() {
	// needs `OC` to resolve the locale chunk URL, so it runs here and not on import
	loadMomentLocale(getLocale())
		.catch((error) => logger.error('Could not load the moment.js locale', { error }))
	interceptRequests()
	initFallbackClipboardAPI()

	initSessionHeartBeat()

	setUpUserMenu()
	setUpContactsMenu()
}
