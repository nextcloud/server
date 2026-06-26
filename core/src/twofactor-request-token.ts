/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { onRequestTokenUpdate } from '@nextcloud/auth'
import { getBaseUrl } from '@nextcloud/router'

document.addEventListener('DOMContentLoaded', () => {
	onRequestTokenUpdate((token) => {
		const cancelLink = window.document.getElementById('cancel-login')
		if (!cancelLink) {
			return
		}

		const href = cancelLink.getAttribute('href')
		if (!href) {
			return
		}

		const parsedHref = new URL(href, getBaseUrl())
		parsedHref.searchParams.set('requesttoken', token)
		cancelLink.setAttribute('href', parsedHref.pathname + parsedHref.search)
	})
})
