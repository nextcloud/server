/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRootUrl } from '@nextcloud/router'
import logger from './logger'

window.addEventListener('DOMContentLoaded', async () => {
	// When the page is loaded send GET to the cron endpoint to trigger background jobs
	try {
		logger.debug('Running web cron')
		await window.fetch(`${getRootUrl()}/cron.php`)
		logger.debug('Web cron successfull')
	} catch (e) {
		logger.debug('Running web cron failed', { error: e })
	}
})
