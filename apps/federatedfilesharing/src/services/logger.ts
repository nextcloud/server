/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getLoggerBuilder } from '@nextcloud/logger'

const logger = getLoggerBuilder()
	.setApp('federatedfilesharing')
	.build()
export default logger
