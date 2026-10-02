/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { initCore } from './init.ts'
import OC from './OC/index.js'

import './globals.js'

window.addEventListener('DOMContentLoaded', function() {
	initCore()
	window.onpopstate = OC.Util.History._onPopState.bind(OC.Util.History)
})

// Fix error "CSRF check failed"
document.addEventListener('DOMContentLoaded', function() {
	const form = document.getElementById('password-input-form') as HTMLFormElement | null
	if (form) {
		form.addEventListener('submit', async function(event) {
			event.preventDefault()
			const requestToken = document.getElementById('requesttoken') as HTMLInputElement | null
			if (requestToken) {
				const url = generateUrl('/csrftoken')
				const resp = await Axios.get(url)
				requestToken.value = resp.data.token
			}
			form.submit()
		})
	}
})
