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

import type { RegistrationResponseJSON } from '@simplewebauthn/types'

import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { startRegistration as registerWebAuthn } from '@simplewebauthn/browser'

import Axios from 'axios'
import axios from '@nextcloud/axios'
import logger from '../logger'

/**
 * Start registering a new device
 * @return The device attributes
 */
export async function startRegistration() {
	const url = generateUrl('/settings/api/personal/webauthn/registration')

	try {
		logger.debug('Fetching webauthn registration data')
		const { data } = await axios.get(url)
		logger.debug('Start webauthn registration')
		const attrs = await registerWebAuthn(data)
		return attrs
	} catch (e) {
		logger.error(e as Error)
		if (Axios.isAxiosError(e)) {
			throw new Error(t('settings', 'Could not register device: Network error'))
		} else if ((e as Error).name === 'InvalidStateError') {
			throw new Error(t('settings', 'Could not register device: Probably already registered'))
		}
		throw new Error(t('settings', 'Could not register device'))
	}
}

/**
 * @param name Name of the device
 * @param data Device attributes
 */
export async function finishRegistration(name: string, data: RegistrationResponseJSON) {
	const url = generateUrl('/settings/api/personal/webauthn/registration')

	const resp = await axios.post(url, { name, data: JSON.stringify(data) })
	return resp.data
}

/**
 * @param id Remove registered device with that id
 */
export async function removeRegistration(id: string | number) {
	const url = generateUrl(`/settings/api/personal/webauthn/registration/${id}`)

	await axios.delete(url)
}
