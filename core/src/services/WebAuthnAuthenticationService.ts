/**
 * @copyright 2020, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
