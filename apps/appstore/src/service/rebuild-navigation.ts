/*!
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { OCSResponse } from '@nextcloud/typings/ocs'

import axios from '@nextcloud/axios'
import { emit } from '@nextcloud/event-bus'
import { generateOcsUrl } from '@nextcloud/router'

/**
 * Rebuilds the app navigation menu
 */
export async function rebuildNavigation() {
	const { data } = await axios.get<OCSResponse>(generateOcsUrl('core/navigation/apps?format=json'))
	if (data.ocs.meta.statuscode !== 200) {
		return
	}

	emit('nextcloud:app-menu.refresh', { apps: data.ocs.data })
	window.dispatchEvent(new Event('resize'))
}
