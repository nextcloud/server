/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getLocale } from '@nextcloud/l10n'
import moment from 'moment'
import { setUp as setUpContactsMenu } from './components/ContactsMenu.ts'
import { setUp as setUpMainMenu } from './components/MainMenu.ts'
import { setUp as setUpUserMenu } from './components/UserMenu.ts'
import { initSessionHeartBeat } from './session-heartbeat.ts'
import { initFallbackClipboardAPI } from './utils/ClipboardFallback.ts'
import { interceptRequests } from './utils/xhr-request.js'

// moment resolves its locales through require(), which ES modules do not provide
import 'moment/min/locales.js'

/**
 * Moment doesn't have aliases for every locale and doesn't parse some locale IDs correctly so we need to alias them
 */
const localeAliases: Record<string, string> = {
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
const locale = localeAliases[getLocale()] ?? getLocale()

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
