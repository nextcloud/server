/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { AuthenticationResponseJSON, PublicKeyCredentialRequestOptionsJSON } from '@simplewebauthn/types'

import { startAuthentication as startWebauthnAuthentication } from '@simplewebauthn/browser'
import { generateUrl } from '@nextcloud/router'

import Axios from '@nextcloud/axios'
import logger from '../logger'

export class NoValidCredentials extends Error {}

/**
 * Start webautn authentication
 * This loads the challenge, connects to the authenticator and returns the repose that needs to be sent to the server.
 *
 * @param loginName Name to login
 */
export async function startAuthentication(loginName: string) {
	const url = generateUrl('/login/webauthn/start')

	const { data } = await Axios.post<PublicKeyCredentialRequestOptionsJSON>(url, { loginName })
	if (!data.allowCredentials || data.allowCredentials.length === 0) {
		logger.error('No valid credentials returned for webauthn')
		throw new NoValidCredentials()
	}
	return await startWebauthnAuthentication(data)
}

/**
 * Verify webauthn authentication
 * @param authData The authentication data to sent to the server
 */
export async function finishAuthentication(authData: AuthenticationResponseJSON) {
	const url = generateUrl('/login/webauthn/finish')

	const { data } = await Axios.post(url, { data: JSON.stringify(authData) })
	return data
}
