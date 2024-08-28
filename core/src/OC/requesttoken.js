/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { emit } from '@nextcloud/event-bus'

/**
 * @private
 * @param {Document} global the document to read the initial value from
 * @param {Function} emit the function to invoke for every new token
 * @return {object}
 */
export const manageToken = (global, emit) => {
	let token = global.getElementsByTagName('head')[0].getAttribute('data-requesttoken')

	return {
		getToken: () => token,
		setToken: newToken => {
			token = newToken

			emit('csrf-token-update', {
				token,
			})
		},
	}
}

const manageFromDocument = manageToken(document, emit)

/**
 * @return {string}
 */
export const getToken = manageFromDocument.getToken

/**
 * @param {string} newToken new token
 */
export const setToken = manageFromDocument.setToken
