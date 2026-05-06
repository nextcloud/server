/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getUserAgentRegex } from 'browserslist-useragent-regexp'
// eslint-disable-next-line n/no-extraneous-import
import browserslist from 'browserslist'
import browserslistConfig from '@nextcloud/browserslist-config'

// Generate a regex that matches user agents to detect incompatible browsers
// Electron is added explicitly as it is used by Cypress tests and desktop app integrations
export const supportedBrowsersRegExp = new RegExp(getUserAgentRegex({ allowHigherVersions: true, browsers: browserslistConfig }).source + '|AscDesktopEditor|Electron')
export const supportedBrowsers = browserslist(browserslistConfig)
