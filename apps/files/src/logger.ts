/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getLoggerBuilder } from '@nextcloud/logger'

export default getLoggerBuilder()
	.setApp('files')
	.detectUser()
	.build()
