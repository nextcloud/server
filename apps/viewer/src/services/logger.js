/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getLoggerBuilder } from '@nextcloud/logger'

// Set up logger
const logger = getLoggerBuilder()
	.setApp(appName)
	.detectUser()
	.build()

export default logger
