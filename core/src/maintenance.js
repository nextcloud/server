/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Axios from '@nextcloud/axios'
import { getRootUrl } from '@nextcloud/router'

const url = getRootUrl() + '/status.php'

const check = () => {
	console.info('checking the Nextcloud maintenance status')
	Axios.get(url)
		.then(resp => resp.data)
		.then(status => {
			if (status.maintenance === false) {
				console.info('Nextcloud is not in maintenance mode anymore -> reloading')

				window.location.reload()
				return
			}

			console.info('Nextcloud is still in maintenance mode')

			// Wait 20sec before the next request
			setTimeout(check, 20 * 1000)
		})
		.catch(console.error.bind(this))
}

// Off we go!
check()
