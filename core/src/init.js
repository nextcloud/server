/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getLocale } from '@nextcloud/l10n'
import moment from 'moment'
import { setUp as setUpContactsMenu } from './components/ContactsMenu.js'
import { setUp as setUpMainMenu } from './components/MainMenu.js'
import { setUp as setUpUserMenu } from './components/UserMenu.js'
import { initSessionHeartBeat } from './session-heartbeat.ts'
import { initFallbackClipboardAPI } from './utils/ClipboardFallback.ts'
import { interceptRequests } from './utils/xhr-request.js'

/**
 * Moment doesn't have aliases for every locale and doesn't parse some locale IDs correctly so we need to alias them
 */
const localeAliases = {
	zh: 'zh-cn',
	zh_Hans: 'zh-cn',
	zh_Hans_CN: 'zh-cn',
	zh_Hans_HK: 'zh-cn',
	zh_Hans_MO: 'zh-cn',
	zh_Hans_SG: 'zh-cn',
	zh_Hant: 'zh-hk',
	zh_Hant_HK: 'zh-hk',
	zh_Hant_MO: 'zh-mo',
	zh_Hant_TW: 'zh-tw',
}
let locale = getLocale()
if (Object.hasOwn(localeAliases, locale)) {
	locale = localeAliases[locale]
}

/**
 * Set users locale to moment.js as soon as possible
 */
moment.locale(locale)

/**
 * Initializes core
 */
export function initCore() {
	interceptRequests()
	initFallbackClipboardAPI()

	initSessionHeartBeat()

	setUpMainMenu()
	setUpUserMenu()
	setUpContactsMenu()
}
