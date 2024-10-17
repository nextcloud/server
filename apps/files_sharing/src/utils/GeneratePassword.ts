/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import Config from '../services/ConfigService.ts'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'

const config = new Config()
// note: some chars removed on purpose to make them human friendly when read out
const passwordSet = 'abcdefgijkmnopqrstwxyzABCDEFGHJKLMNPQRSTWXYZ23456789'

/**
 * Generate a valid policy password or request a valid password if password_policy is enabled
 *
 * @param {boolean} verbose If enabled the the status is shown to the user via toast
 */
export default async function(verbose = false): Promise<string> {
	// password policy is enabled, let's request a pass
	if (config.passwordPolicy.api && config.passwordPolicy.api.generate) {
		try {
			const request = await axios.get(config.passwordPolicy.api.generate)
			if (request.data.ocs.data.password) {
				if (verbose) {
					showSuccess(t('files_sharing', 'Password created successfully'))
				}
				return request.data.ocs.data.password
			}
		} catch (error) {
			console.info('Error generating password from password_policy', error)
			if (verbose) {
				showError(t('files_sharing', 'Error generating password from password policy'))
			}
		}
	}

	const array = new Uint8Array(10)
	const ratio = passwordSet.length / 255
	self.crypto.getRandomValues(array)
	let password = ''
	for (let i = 0; i < array.length; i++) {
		password += passwordSet.charAt(array[i] * ratio)
	}
	return password
}
