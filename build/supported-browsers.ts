/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import browserslistConfig from '@nextcloud/browserslist-config'
import { getUserAgentRegex } from 'browserslist-useragent-regexp'

/**
 * Build-time constants for the unsupported browser check,
 * so browserslist and caniuse-lite are not shipped to the browser.
 */
export const supportedBrowsersDefine = {
	__SUPPORTED_BROWSERS_REGEXP__: JSON.stringify(getUserAgentRegex({ allowHigherVersions: true, browsers: browserslistConfig }).source + '|AscDesktopEditor'),
}
