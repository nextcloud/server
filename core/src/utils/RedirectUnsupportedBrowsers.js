/**
 * @copyright 2022 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import { supportedBrowsersRegExp } from '../services/BrowsersListService.js'
import browserStorage from '../services/BrowserStorageService.js'
import logger from '../logger.js'

export const browserStorageKey = 'unsupported-browser-ignore'
const redirectPath = '/unsupported'

const isBrowserOverridden = browserStorage.getItem(browserStorageKey) === 'true'

/**
 * Test the current browser user agent against our official browserslist config
 * and redirect if unsupported
 */
export const testSupportedBrowser = function() {
	if (supportedBrowsersRegExp.test(navigator.userAgent)) {
		logger.debug('this browser is officially supported ! 🚀')
		return
	}

	// If incompatible BUT ignored, let's keep going
	if (isBrowserOverridden) {
		logger.debug('this browser is NOT supported but has been manually overridden ! ⚠️')
		return
	}

	// If incompatible, NOT overridden AND NOT already on the warning page,
	// redirect to the unsupported warning page
	if (window.location.pathname.indexOf(redirectPath) === -1) {
		const redirectUrl = window.location.href.replace(window.location.origin, '')
		const base64Param = Buffer.from(redirectUrl).toString('base64')
		history.pushState(null, null, `${redirectPath}?redirect_url=${base64Param}`)
		window.location.reload()
	}
}
