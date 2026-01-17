/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { showWarning } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'

window.addEventListener('DOMContentLoaded', async function() {
	if (getCurrentUser() === null) {
		// skip for public pages
		return
	}

	const { data } = await axios.get(generateUrl('/apps/encryption/ajax/getStatus'))
	if (data.status === 'interactionNeeded') {
		showWarning(data.data.message)
	}
})
