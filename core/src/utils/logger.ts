/*!
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getLoggerBuilder } from '@nextcloud/logger'

export const logger = getLoggerBuilder()
	.setApp('core')
	.detectUser()
	.build()

export const unifiedSearchLogger = getLoggerBuilder()
	.setApp('unified-search')
	.detectUser()
	.build()
