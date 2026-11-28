/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import moment from 'moment'

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

// `moment` resolves to its ES build, whose locale files register on that same instance
const momentLocales = import.meta.glob('/node_modules/moment/dist/locale/*.js')

/**
 * Load a locale into moment.js and make it the global locale.
 * Only the requested locale is fetched instead of bundling all of them.
 *
 * @param nextcloudLocale - The Nextcloud locale, e.g. `de_DE`
 */
export async function loadMomentLocale(nextcloudLocale: string): Promise<void> {
	const locale = localeAliases[nextcloudLocale] ?? nextcloudLocale
	const file = [locale.toLowerCase().replace('_', '-'), locale.split(/[_-]/)[0]!.toLowerCase()]
		.map((name) => `/node_modules/moment/dist/locale/${name}.js`)
		.find((file) => file in momentLocales)
	if (file) {
		await momentLocales[file]!()
	}
	moment.locale(locale)
}
