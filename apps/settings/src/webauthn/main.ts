/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { createApp } from 'vue'
import WebAuthnSection from './components/WebAuthnSection.vue'

const app = createApp(WebAuthnSection, {
	initialDevices: loadState('settings', 'webauthn-devices'),
	isHttps: window.location.protocol === 'https:',
	isLocalhost: window.location.hostname === 'localhost',
})
app.mixin({ methods: { t } })
app.mount('#security-webauthn')
