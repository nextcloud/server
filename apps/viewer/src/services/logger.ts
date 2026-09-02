/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getLoggerBuilder } from '@nextcloud/logger'

// Set up logger
export const logger = getLoggerBuilder()
	.setApp('viewer')
	.detectUser()
	.build()
