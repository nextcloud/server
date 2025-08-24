/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import 'core-js/stable/index.js'
import 'regenerator-runtime/runtime.js'

// If you remove the line below, tests won't pass
// eslint-disable-next-line no-unused-vars
import OC from './OC/index.js'

import './globals.js'
import './jquery/index.js'
import { initCore } from './init.js'
import { registerAppsSlideToggle } from './OC/apps.js'
import { getCSPNonce } from '@nextcloud/auth'
import { generateUrl } from '@nextcloud/router'
import Axios from '@nextcloud/axios'

// eslint-disable-next-line camelcase
__webpack_nonce__ = getCSPNonce()

window.addEventListener('DOMContentLoaded', function() {
	initCore()
	registerAppsSlideToggle()

	// fallback to hashchange when no history support
	if (window.history.pushState) {
		window.onpopstate = _.bind(OC.Util.History._onPopState, OC.Util.History)
	} else {
		window.onhashchange = _.bind(OC.Util.History._onPopState, OC.Util.History)
	}
})

// Fix error "CSRF check failed"
document.addEventListener('DOMContentLoaded', function() {
	const form = document.getElementById('password-input-form')
	if (form) {
		form.addEventListener('submit', async function(event) {
			event.preventDefault()
			const requestToken = document.getElementById('requesttoken')
			if (requestToken) {
				const url = generateUrl('/csrftoken')
				const resp = await Axios.get(url)
				requestToken.value = resp.data.token
			}
			form.submit()
		})
	}
})
