/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import _ from 'underscore'
import { initCore } from './init.js'
import { registerAppsSlideToggle } from './OC/apps.js'
import OC from './OC/index.js'

import 'core-js/stable/index.js'
import 'regenerator-runtime/runtime.js'
import './globals.js'
import './jquery/index.js'

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
