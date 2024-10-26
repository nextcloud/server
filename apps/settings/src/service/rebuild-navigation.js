/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { emit } from '@nextcloud/event-bus'

export default () => {
	return axios.get(generateOcsUrl('core/navigation', 2) + '/apps?format=json')
		.then(({ data }) => {
			if (data.ocs.meta.statuscode !== 200) {
				return
			}

			emit('nextcloud:app-menu.refresh', { apps: data.ocs.data })
			window.dispatchEvent(new Event('resize'))
		})
}
