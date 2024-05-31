/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 *
 */
export function generateCodes() {
	const url = generateUrl('/apps/twofactor_backupcodes/settings/create')

	return Axios.post(url, {}).then(resp => resp.data)
}
