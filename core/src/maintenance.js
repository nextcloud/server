/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Axios from '@nextcloud/axios'
import { getRootUrl } from '@nextcloud/router'
import logger from './logger.js'

const url = getRootUrl() + '/status.php'

/**
 *
 */
function check() {
	logger.info('checking the Nextcloud maintenance status')
	Axios.get(url)
		.then((resp) => resp.data)
		.then((status) => {
			if (status.maintenance === false) {
				logger.info('Nextcloud is not in maintenance mode anymore -> reloading')

				window.location.reload()
				return
			}

			logger.info('Nextcloud is still in maintenance mode')

			// Wait 20sec before the next request
			setTimeout(check, 20 * 1000)
		})
		.catch(logger.error.bind(this))
}

// Off we go!
check()
