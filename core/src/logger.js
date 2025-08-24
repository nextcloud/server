/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'
import { getLoggerBuilder } from '@nextcloud/logger'

const getLogger = user => {
	if (user === null) {
		return getLoggerBuilder()
			.setApp('core')
			.build()
	}
	return getLoggerBuilder()
		.setApp('core')
		.setUid(user.uid)
		.build()
}

export default getLogger(getCurrentUser())

export const unifiedSearchLogger = getLoggerBuilder()
	.setApp('unified-search')
	.detectUser()
	.build()
