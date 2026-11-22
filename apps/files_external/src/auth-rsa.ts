/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAuthMechanism } from './types.ts'

import { defineAsyncComponent, defineCustomElement } from 'vue'

const AuthMechanismRsa = defineAsyncComponent(() => import('./views/AuthMechanismRsa.vue'))
const AuthMechanismRsaComponent = defineCustomElement(AuthMechanismRsa, { shadowRoot: false })
customElements.define('files_external-auth-mechanism-rsa', AuthMechanismRsaComponent)

window.OCA.FilesExternal.AuthMechanism!.registerHandler({
	id: 'rsa',
	tagName: 'files_external-auth-mechanism-rsa',
	enabled(authMechanism: IAuthMechanism) {
		return authMechanism.scheme === 'publickey' && authMechanism.identifier === 'publickey::rsa'
	},
})
