/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { RegistrationResponseJSON } from '@simplewebauthn/types'

import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { startRegistration as registerWebAuthn } from '@simplewebauthn/browser'

import axios, { isAxiosError } from '@nextcloud/axios'
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
		if (isAxiosError(e)) {
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
